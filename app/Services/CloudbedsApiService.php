<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CloudbedsApiService
{
    public function roomTypes(): array
    {
        $cacheSeconds = max(60, (int) config('cloudbeds.api.cache_seconds', 300));
        $cacheKey = 'cloudbeds.room_types.v3.current';
        $staleKey = 'cloudbeds.room_types.v3.last_success';

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = $this->request()->get($this->baseUrl().'/getRooms', [
                'pageNumber' => 1,
                'pageSize' => 100,
                'sort' => 'sorting_position',
            ]);

            $response->throw();
            $roomTypes = $this->normalizeRoomTypes($response->json());

            if ($roomTypes === []) {
                throw new RuntimeException('Cloudbeds returned no room types.');
            }

            Cache::put($cacheKey, $roomTypes, now()->addSeconds($cacheSeconds));
            Cache::put($staleKey, $roomTypes, now()->addDay());

            return $roomTypes;
        } catch (Throwable $exception) {
            $stale = Cache::get($staleKey);

            if (is_array($stale) && $stale !== []) {
                return $stale;
            }

            throw $exception;
        }
    }

    public function availableRoomTypes(
        CarbonInterface|string $checkin,
        CarbonInterface|string $checkout,
        int $rooms = 1,
        int $adults = 1,
        int $children = 0,
    ): array {
        $startDate = $checkin instanceof CarbonInterface ? $checkin->toDateString() : (string) $checkin;
        $endDate = $checkout instanceof CarbonInterface ? $checkout->toDateString() : (string) $checkout;

        $query = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rooms' => max(1, $rooms),
            'adults' => max(1, $adults),
            'children' => max(0, $children),
            'detailedRates' => 'true',
            'pageNumber' => 1,
            'pageSize' => 100,
        ];

        if (filled(config('cloudbeds.api.property_id'))) {
            $query['propertyIDs'] = (string) config('cloudbeds.api.property_id');
        }

        $response = $this->request()->get($this->baseUrl().'/getAvailableRoomTypes', $query);
        $response->throw();

        $availableIds = $this->extractRoomTypeIds($response->json());
        if ($availableIds === []) {
            return [];
        }

        return collect($this->roomTypes())
            ->filter(function (array $roomType) use ($availableIds): bool {
                return collect((array) ($roomType['room_type_ids'] ?? []))
                    ->contains(fn ($id): bool => in_array((string) $id, $availableIds, true));
            })
            ->map(function (array $roomType) use ($availableIds): array {
                $matchingId = collect((array) ($roomType['room_type_ids'] ?? []))
                    ->map(fn ($id): string => (string) $id)
                    ->first(fn (string $id): bool => in_array($id, $availableIds, true));

                if ($matchingId) {
                    $roomType['id'] = $matchingId;
                }

                $roomType['available'] = true;

                return $roomType;
            })
            ->values()
            ->all();
    }

    public function availableRoomTypeForApartment(
        Apartment $apartment,
        CarbonInterface|string $checkin,
        CarbonInterface|string $checkout,
        int $adults = 1,
        int $rooms = 1,
    ): ?array {
        $available = $this->availableRoomTypes($checkin, $checkout, $rooms, $adults);

        return $this->matchApartment($apartment, $available);
    }

    public function matchApartment(Apartment $apartment, ?array $roomTypes = null): ?array
    {
        $roomTypes ??= $this->roomTypes();
        $apartmentKeys = $this->matchKeys([
            $apartment->name,
            $apartment->slug,
            $apartment->apartment_id,
        ]);

        foreach ($roomTypes as $roomType) {
            $roomKeys = $this->matchKeys([
                $roomType['name'] ?? null,
                $roomType['full_name'] ?? null,
                $roomType['short_name'] ?? null,
            ]);

            if (array_intersect($apartmentKeys, $roomKeys) !== []) {
                return $roomType;
            }
        }

        foreach ($roomTypes as $roomType) {
            $roomKeys = $this->matchKeys([
                $roomType['name'] ?? null,
                $roomType['full_name'] ?? null,
                $roomType['short_name'] ?? null,
            ]);

            foreach ($apartmentKeys as $apartmentKey) {
                foreach ($roomKeys as $roomKey) {
                    if (strlen($apartmentKey) < 4 || strlen($roomKey) < 4) {
                        continue;
                    }

                    if (str_contains($apartmentKey, $roomKey) || str_contains($roomKey, $apartmentKey)) {
                        return $roomType;
                    }
                }
            }
        }

        return null;
    }

    public function createReservation(Invoice $invoice, array $booking, ?array $roomType = null): array
    {
        $payload = $invoice->payment_payload ?? [];
        $existing = data_get($payload, 'cloudbeds.reservation');

        if (is_array($existing) && filled(data_get($existing, 'reservation_id'))) {
            return $existing;
        }

        $invoice->loadMissing('invoiceItems.apartment');
        $item = $invoice->invoiceItems->first();
        $apartment = $item?->apartment;

        if (! $item || ! $apartment) {
            throw new RuntimeException('Cloudbeds sync could not find the booked apartment.');
        }

        $checkin = $item->checkin;
        $checkout = $item->checkout;
        $guestCount = max(1, (int) data_get($booking, 'guests', 1));

        $roomType ??= $this->availableRoomTypeForApartment($apartment, $checkin, $checkout, $guestCount);
        if (! $roomType || blank($roomType['id'] ?? null)) {
            throw new RuntimeException('The selected Cloudbeds room type is no longer available for this stay.');
        }

        [$firstName, $lastName] = $this->guestNames($invoice, $booking);
        $roomTypeId = (string) $roomType['id'];

        $propertyId = trim((string) (config('cloudbeds.api.property_id') ?: ($roomType['property_id'] ?? '')));
        if ($propertyId === '') {
            throw new RuntimeException('Cloudbeds property ID could not be determined.');
        }

        $form = [
            'propertyID' => $propertyId,
            'startDate' => $checkin->toDateString(),
            'endDate' => $checkout->toDateString(),
            'guestFirstName' => $firstName,
            'guestLastName' => $lastName,
            'guestCountry' => $this->countryCode((string) ($invoice->country ?: data_get($booking, 'country', 'Nigeria'))),
            'guestEmail' => (string) $invoice->email,
            'guestPhone' => (string) $invoice->phone,
            'rooms' => [[
                'roomTypeID' => $roomTypeId,
                'quantity' => 1,
            ]],
            'adults' => [[
                'roomTypeID' => $roomTypeId,
                'quantity' => $guestCount,
            ]],
            'children' => [[
                'roomTypeID' => $roomTypeId,
                'quantity' => 0,
            ]],
            'thirdPartyIdentifier' => (string) $invoice->invoice,
            'paymentMethod' => (string) config('cloudbeds.api.payment_method', 'ebanking'),
            'sendEmailConfirmation' => false,
        ];


        if (filled(config('cloudbeds.api.source_id'))) {
            $form['sourceID'] = (string) config('cloudbeds.api.source_id');
        }

        $response = $this->request()
            ->asForm()
            ->post($this->baseUrl().'/postReservation', $form);

        $response->throw();
        $body = $response->json();
        $reservationId = (string) (
            data_get($body, 'data.reservationID')
            ?? data_get($body, 'reservationID')
            ?? data_get($body, 'data.id')
            ?? ''
        );

        if ($reservationId === '') {
            throw new RuntimeException('Cloudbeds created no reservation ID.');
        }

        $reservation = [
            'reservation_id' => $reservationId,
            'room_type_id' => $roomTypeId,
            'room_type_name' => (string) ($roomType['name'] ?? $apartment->name),
            'synced_at' => now()->toIso8601String(),
            'response' => $body,
        ];

        $payload['cloudbeds'] = [
            'status' => 'synced',
            'reservation' => $reservation,
        ];
        $invoice->forceFill(['payment_payload' => $payload])->save();

        Log::info('Paid Maison Be reservation synced to Cloudbeds.', [
            'invoice_id' => $invoice->id,
            'invoice' => $invoice->invoice,
            'reservation_id' => $reservationId,
            'room_type_id' => $roomTypeId,
        ]);

        return $reservation;
    }

    public function recordSyncFailure(Invoice $invoice, Throwable $exception): void
    {
        $payload = $invoice->payment_payload ?? [];
        $payload['cloudbeds'] = [
            'status' => 'failed',
            'failed_at' => now()->toIso8601String(),
            'message' => $exception->getMessage(),
        ];
        $invoice->forceFill(['payment_payload' => $payload])->save();

        Log::error('Paid Maison Be reservation could not sync to Cloudbeds.', [
            'invoice_id' => $invoice->id,
            'invoice' => $invoice->invoice,
            'message' => $exception->getMessage(),
        ]);
    }

    private function request()
    {
        $apiKey = trim((string) config('cloudbeds.api.key'));

        if ($apiKey === '') {
            throw new RuntimeException('Cloudbeds API key is not configured.');
        }

        return Http::acceptJson()
            ->withHeaders(['x-api-key' => $apiKey])
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 300);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('cloudbeds.api.base_url', 'https://api.cloudbeds.com/api/v1.3'), '/');
    }

    private function normalizeRoomTypes(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $rooms = $this->extractRooms($payload);
        $types = [];

        foreach ($rooms as $room) {
            if (! is_array($room)) {
                continue;
            }

            $fullName = trim((string) ($room['roomTypeName'] ?? $room['roomName'] ?? $room['name'] ?? ''));
            $shortName = trim((string) ($room['roomTypeNameShort'] ?? ''));
            $rawName = $shortName !== '' ? $shortName : $fullName;

            if ($rawName === '') {
                continue;
            }

            $displayName = $this->displayRoomTypeName($fullName, $shortName);
            $canonicalName = $this->canonicalRoomTypeName($displayName !== '' ? $displayName : $rawName);
            $id = trim((string) ($room['roomTypeID'] ?? $room['roomTypeId'] ?? $room['room_type_id'] ?? ''));
            $propertyId = trim((string) ($room['propertyID'] ?? $room['propertyId'] ?? $room['property_id'] ?? ''));

            $identity = $canonicalName !== ''
                ? 'name:'.$canonicalName
                : ($id !== '' ? 'id:'.$id : 'raw:'.$this->normalizeName($rawName));

            $maxGuests = max(0, (int) ($room['maxGuests'] ?? $room['max_guests'] ?? 0));
            $description = trim((string) ($room['roomDescription'] ?? $room['description'] ?? ''));

            if (! isset($types[$identity])) {
                $types[$identity] = [
                    'id' => $id,
                    'room_type_ids' => $id !== '' ? [$id] : [],
                    'property_id' => $propertyId,
                    'name' => $displayName !== '' ? $displayName : $rawName,
                    'full_name' => $fullName,
                    'short_name' => $shortName,
                    'description' => $description,
                    'max_guests' => $maxGuests,
                    'units' => 1,
                ];
                continue;
            }

            if ($id !== '' && ! in_array($id, $types[$identity]['room_type_ids'], true)) {
                $types[$identity]['room_type_ids'][] = $id;
            }

            if ($types[$identity]['id'] === '' && $id !== '') {
                $types[$identity]['id'] = $id;
            }

            if ($types[$identity]['property_id'] === '' && $propertyId !== '') {
                $types[$identity]['property_id'] = $propertyId;
            }

            if ($types[$identity]['description'] === '' && $description !== '') {
                $types[$identity]['description'] = $description;
            }

            $types[$identity]['max_guests'] = max($types[$identity]['max_guests'], $maxGuests);
            $types[$identity]['units']++;
        }

        return array_values($types);
    }

    private function extractRooms(array $payload): array
    {
        $data = $payload['data'] ?? $payload['rooms'] ?? [];

        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            $rooms = [];

            foreach ($data as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (isset($item['rooms']) && is_array($item['rooms'])) {
                    $propertyId = $item['propertyID'] ?? $item['propertyId'] ?? $item['property_id'] ?? null;

                    foreach ($item['rooms'] as $room) {
                        if (! is_array($room)) {
                            continue;
                        }

                        if ($propertyId !== null && ! isset($room['propertyID'], $room['propertyId'], $room['property_id'])) {
                            $room['propertyID'] = $propertyId;
                        }

                        $rooms[] = $room;
                    }
                    continue;
                }

                $rooms[] = $item;
            }

            return $rooms;
        }

        if (isset($data['rooms']) && is_array($data['rooms'])) {
            return $data['rooms'];
        }

        return [];
    }

    private function extractRoomTypeIds(mixed $payload): array
    {
        $ids = [];
        $walk = function (mixed $value) use (&$walk, &$ids): void {
            if (! is_array($value)) {
                return;
            }

            foreach (['roomTypeID', 'roomTypeId', 'room_type_id'] as $key) {
                if (isset($value[$key]) && (string) $value[$key] !== '') {
                    $ids[] = (string) $value[$key];
                }
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($payload);

        return array_values(array_unique($ids));
    }

    private function displayRoomTypeName(string $fullName, string $shortName): string
    {
        $fullName = $this->cleanRoomTypeName($fullName);
        $shortName = $this->cleanRoomTypeName($shortName);

        // Cloudbeds exposes the Penthouse short name without its BELVEDERE prefix.
        // Keep the complete public accommodation name for that one room type.
        if (str_contains(strtolower($fullName), 'penthouse') && $fullName !== '') {
            return $fullName;
        }

        return $shortName !== '' ? $shortName : $fullName;
    }

    private function cleanRoomTypeName(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+(?:at|@)\s+maison\s*be(?:\s+residences?)?\s*$/i', '', $value) ?? $value;
        $value = preg_replace('/\s*[-|:]\s*maison\s*be(?:\s+residences?)?\s*$/i', '', $value) ?? $value;
        $value = preg_replace('/^maison\s*be(?:\s+residences?)?\s*[-|:]\s*/i', '', $value) ?? $value;

        return trim($value);
    }

    private function canonicalRoomTypeName(string $value): string
    {
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($value)) ?: [];
        $ignored = ['at', 'maison', 'be', 'residence', 'residences', 'apartment', 'apartments', 'suite', 'suites', 'room', 'rooms'];
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== '' && ! in_array($token, $ignored, true)));

        return implode('', $tokens);
    }

    private function matchKeys(array $values): array
    {
        $keys = [];

        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            foreach ([$value, $this->cleanRoomTypeName($value)] as $variant) {
                $key = $this->canonicalRoomTypeName($variant);
                if ($key !== '') {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }

    private function guestNames(Invoice $invoice, array $booking): array
    {
        $first = trim((string) data_get($booking, 'first_name'));
        $last = trim((string) data_get($booking, 'last_name'));

        if ($first !== '' && $last !== '') {
            return [$first, $last];
        }

        $parts = preg_split('/\s+/', trim((string) $invoice->full_name), 2) ?: [];

        return [
            $parts[0] ?? 'Guest',
            $parts[1] ?? '-',
        ];
    }

    private function countryCode(string $country): string
    {
        $country = trim($country);
        if (preg_match('/^[a-z]{2}$/i', $country)) {
            return strtoupper($country);
        }

        $map = [
            'nigeria' => 'NG', 'ghana' => 'GH', 'south africa' => 'ZA', 'united states' => 'US',
            'usa' => 'US', 'united states of america' => 'US', 'canada' => 'CA', 'united kingdom' => 'GB',
            'uk' => 'GB', 'england' => 'GB', 'ireland' => 'IE', 'france' => 'FR', 'germany' => 'DE',
            'italy' => 'IT', 'spain' => 'ES', 'portugal' => 'PT', 'netherlands' => 'NL', 'belgium' => 'BE',
            'switzerland' => 'CH', 'austria' => 'AT', 'united arab emirates' => 'AE', 'uae' => 'AE',
            'saudi arabia' => 'SA', 'qatar' => 'QA', 'kenya' => 'KE', 'rwanda' => 'RW', 'uganda' => 'UG',
            'cameroon' => 'CM', 'senegal' => 'SN', 'cote d ivoire' => 'CI', "côte d'ivoire" => 'CI',
            'australia' => 'AU', 'new zealand' => 'NZ', 'india' => 'IN', 'china' => 'CN', 'japan' => 'JP',
            'brazil' => 'BR', 'mexico' => 'MX', 'turkey' => 'TR', 'egypt' => 'EG',
        ];

        return $map[strtolower($country)] ?? 'NG';
    }
}
