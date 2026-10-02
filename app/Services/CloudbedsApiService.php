<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CloudbedsApiService
{
    public function roomTypes(): array
    {
        $apiKey = trim((string) config('cloudbeds.api.key'));
        $baseUrl = rtrim((string) config('cloudbeds.api.base_url', 'https://api.cloudbeds.com/api/v1.3'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('Cloudbeds API key is not configured.');
        }

        $cacheSeconds = max(60, (int) config('cloudbeds.api.cache_seconds', 300));

        // Version the cache key whenever normalization changes so an older response
        // cannot keep duplicate physical rooms on the apartments page.
        $cacheKey = 'cloudbeds.room_types.v2.current';
        $staleKey = 'cloudbeds.room_types.v2.last_success';

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-api-key' => $apiKey])
                ->connectTimeout(5)
                ->timeout(12)
                ->retry(2, 300)
                ->get($baseUrl.'/getRooms', [
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

            $displayName = $this->cleanRoomTypeName($rawName);
            $canonicalName = $this->canonicalRoomTypeName($displayName !== '' ? $displayName : $rawName);
            $id = trim((string) ($room['roomTypeID'] ?? $room['roomTypeId'] ?? $room['room_type_id'] ?? ''));

            // The getRooms response is physical-room based. Some Cloudbeds setups
            // can expose more than one roomTypeID with the same public short name.
            // The website should still show one accommodation card per public type.
            $identity = $canonicalName !== ''
                ? 'name:'.$canonicalName
                : ($id !== '' ? 'id:'.$id : 'raw:'.$this->normalizeName($rawName));

            $maxGuests = max(0, (int) ($room['maxGuests'] ?? $room['max_guests'] ?? 0));
            $description = trim((string) ($room['roomDescription'] ?? $room['description'] ?? ''));

            if (! isset($types[$identity])) {
                $types[$identity] = [
                    'id' => $id,
                    'room_type_ids' => $id !== '' ? [$id] : [],
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

                // v1.3 getRooms commonly wraps physical rooms by property.
                if (isset($item['rooms']) && is_array($item['rooms'])) {
                    array_push($rooms, ...$item['rooms']);
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

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }
}
