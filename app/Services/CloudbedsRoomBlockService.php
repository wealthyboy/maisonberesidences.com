<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\ApartmentDateBlock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CloudbedsRoomBlockService
{
    private const WEBHOOK_HEADER = 'X-MaisonBe-Cloudbeds-Webhook';

    public function __construct(private readonly CloudbedsApiService $cloudbeds)
    {
    }

    public function ensureWebhookSubscriptions(): int
    {
        $propertyId = $this->propertyId();
        $endpoint = route('webhooks.cloudbeds-availability');
        $existing = $this->webhookRequest()
            ->get($this->webhookBaseUrl().'/getWebhooks', ['propertyID' => $propertyId]);

        $existing->throw();
        $subscriptions = collect((array) data_get($existing->json(), 'data', []));
        $created = 0;

        foreach ($this->requiredWebhookEvents() as [$object, $action]) {
            $alreadyExists = $subscriptions->contains(function ($subscription) use ($object, $action, $endpoint): bool {
                if (! is_array($subscription)) {
                    return false;
                }

                $entity = (string) data_get($subscription, 'event.entity', '');
                $existingAction = (string) data_get($subscription, 'event.action', '');
                $url = rtrim((string) data_get($subscription, 'subscriptionData.url', ''), '/');

                return strcasecmp($entity, $object) === 0
                    && strcasecmp($existingAction, $action) === 0
                    && $url === rtrim($endpoint, '/');
            });

            if ($alreadyExists) {
                continue;
            }

            $response = $this->webhookRequest()
                ->asForm()
                ->post($this->webhookBaseUrl().'/postWebhook', [
                    'propertyID' => $propertyId,
                    'object' => $object,
                    'action' => $action,
                    'endpointUrl' => $endpoint,
                    'authHeaderName' => self::WEBHOOK_HEADER,
                    'authHeaderValue' => $this->webhookSecret(),
                ]);

            $response->throw();
            $created++;
        }

        return $created;
    }

    public function syncExistingBlocks(int $days = 500): array
    {
        $days = max(1, min(500, $days));
        $propertyId = $this->propertyId();
        $cursor = today();
        $lastDay = today()->addDays($days - 1);
        $seen = [];
        $synced = 0;

        while ($cursor->lte($lastDay)) {
            $windowEnd = $cursor->copy()->addDays(34);
            if ($windowEnd->gt($lastDay)) {
                $windowEnd = $lastDay->copy();
            }

            $page = 1;
            do {
                $response = $this->apiRequest()->get($this->apiBaseUrl().'/getRoomBlocks', [
                    'propertyID' => $propertyId,
                    'startDate' => $cursor->toDateString(),
                    'endDate' => $windowEnd->toDateString(),
                    'pageNumber' => $page,
                    'pageSize' => 100,
                ]);
                $response->throw();

                $blocks = $this->extractRoomBlocks($response->json());
                foreach ($blocks as $block) {
                    $externalId = trim((string) ($block['roomBlockID'] ?? $block['roomBlockId'] ?? ''));
                    if ($externalId === '') {
                        continue;
                    }

                    $seen[$externalId] = true;
                    if ($this->upsertRoomBlock($block)) {
                        $synced++;
                    }
                }

                $page++;
            } while (count($blocks) === 100);

            $cursor = $windowEnd->copy()->addDay();
        }

        // If a webhook was missed while the site was unavailable, the full
        // reconciliation still removes stale Cloudbeds replicas.
        ApartmentDateBlock::query()
            ->where('source', 'cloudbeds')
            ->where('external_type', 'not like', 'availability_closeout%')
            ->whereDate('ends_on', '>=', today())
            ->when($seen !== [], fn ($query) => $query->whereNotIn('external_id', array_keys($seen)))
            ->when($seen === [], fn ($query) => $query)
            ->delete();

        return [
            'days' => $days,
            'seen' => count($seen),
            'synced' => $synced,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $event = strtolower(trim((string) ($payload['event'] ?? '')));

        if (str_starts_with($event, 'roomblock/')) {
            $externalId = trim((string) ($payload['roomBlockID'] ?? $payload['roomBlockId'] ?? ''));

            if ($externalId === '') {
                throw new RuntimeException('Cloudbeds room block webhook did not contain a roomBlockID.');
            }

            if ($event === 'roomblock/removed') {
                ApartmentDateBlock::query()
                    ->where('source', 'cloudbeds')
                    ->where('external_id', $externalId)
                    ->delete();

                return;
            }

            $this->upsertRoomBlock($payload);
            return;
        }

        // Accommodation Blocked in Cloudbeds' availability matrix uses a
        // separate event. Mirror only the base-rate closeout (ratePlanID=0),
        // because closing one optional rate plan must not make the whole
        // Maison Be apartment unavailable.
        if ($event === 'availability/closeout_changed') {
            $this->handleBaseRateCloseout($payload);
        }
    }

    public function webhookIsValid(?string $provided): bool
    {
        $provided = trim((string) $provided);

        return $provided !== '' && hash_equals($this->webhookSecret(), $provided);
    }

    public function webhookHeaderName(): string
    {
        return self::WEBHOOK_HEADER;
    }

    private function upsertRoomBlock(array $payload): bool
    {
        $externalId = trim((string) ($payload['roomBlockID'] ?? $payload['roomBlockId'] ?? ''));
        $startDate = trim((string) ($payload['startDate'] ?? ''));
        $endDate = trim((string) ($payload['endDate'] ?? ''));

        if ($externalId === '' || $startDate === '' || $endDate === '') {
            return false;
        }

        $apartments = $this->apartmentsForRooms((array) ($payload['rooms'] ?? []));
        if ($apartments->isEmpty()) {
            Log::warning('Cloudbeds room block could not be mapped to a Maison Be apartment.', [
                'room_block_id' => $externalId,
                'rooms' => $payload['rooms'] ?? [],
            ]);

            return false;
        }

        $type = trim((string) ($payload['roomBlockType'] ?? 'blocked_dates')) ?: 'blocked_dates';
        $reason = trim((string) ($payload['roomBlockReason'] ?? ''));
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        // Maison Be stores ranges as [start, available-again). If Cloudbeds
        // ever reports a one-day block with identical start/end dates, keep it
        // as a real one-day block rather than a zero-length range.
        if ($end->lte($start)) {
            $end = $start->copy()->addDay();
        }

        DB::transaction(function () use ($externalId, $start, $end, $type, $reason, $payload, $apartments): void {
            $block = ApartmentDateBlock::query()->updateOrCreate(
                ['source' => 'cloudbeds', 'external_id' => $externalId],
                [
                    'title' => 'Cloudbeds · '.$this->humanBlockType($type),
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                    'reason' => $reason !== '' ? $reason : 'Managed in Cloudbeds',
                    'external_type' => $type,
                    'external_payload' => $payload,
                    'synced_at' => now(),
                    'created_by' => null,
                ],
            );

            $block->apartments()->sync($apartments->modelKeys());
        });

        return true;
    }

    private function handleBaseRateCloseout(array $payload): void
    {
        $ratePlanId = trim((string) ($payload['ratePlanID'] ?? $payload['ratePlanId'] ?? '0'));
        if ($ratePlanId !== '' && $ratePlanId !== '0') {
            return;
        }

        $roomTypeId = trim((string) ($payload['roomTypeID'] ?? $payload['roomTypeId'] ?? ''));
        $date = trim((string) ($payload['date'] ?? ''));
        $status = strtolower(trim((string) ($payload['status'] ?? '')));

        if ($roomTypeId === '' || $date === '') {
            return;
        }

        $externalId = 'closeout:'.$roomTypeId.':'.$date;

        if ($status !== 'closed') {
            ApartmentDateBlock::query()
                ->where('source', 'cloudbeds')
                ->where('external_id', $externalId)
                ->delete();
            return;
        }

        $apartments = $this->apartmentsForRoomTypeId($roomTypeId);
        if ($apartments->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($externalId, $date, $payload, $apartments): void {
            $block = ApartmentDateBlock::query()->updateOrCreate(
                ['source' => 'cloudbeds', 'external_id' => $externalId],
                [
                    'title' => 'Cloudbeds · Accommodation blocked',
                    'starts_on' => Carbon::parse($date)->toDateString(),
                    'ends_on' => Carbon::parse($date)->addDay()->toDateString(),
                    'reason' => 'Base-rate accommodation closeout in Cloudbeds',
                    'external_type' => 'availability_closeout',
                    'external_payload' => $payload,
                    'synced_at' => now(),
                    'created_by' => null,
                ],
            );

            $block->apartments()->sync($apartments->modelKeys());
        });
    }

    private function apartmentsForRooms(array $rooms): Collection
    {
        $ids = collect($rooms)
            ->filter(fn ($room): bool => is_array($room))
            ->map(fn (array $room): string => trim((string) ($room['roomTypeID'] ?? $room['roomTypeId'] ?? '')))
            ->filter()
            ->unique();

        return $ids
            ->flatMap(fn (string $roomTypeId): Collection => $this->apartmentsForRoomTypeId($roomTypeId))
            ->unique('id')
            ->values();
    }

    private function apartmentsForRoomTypeId(string $roomTypeId): Collection
    {
        $roomType = collect($this->cloudbeds->roomTypes())->first(function (array $candidate) use ($roomTypeId): bool {
            return in_array($roomTypeId, array_map('strval', (array) ($candidate['room_type_ids'] ?? [])), true)
                || (string) ($candidate['id'] ?? '') === $roomTypeId;
        });

        $apartments = Apartment::query()->orderBy('sort_order')->orderBy('id')->get();

        if (is_array($roomType)) {
            $matched = $apartments->filter(function (Apartment $apartment) use ($roomType): bool {
                return $this->cloudbeds->matchApartment($apartment, [$roomType]) !== null;
            });

            if ($matched->isNotEmpty()) {
                return $matched->values();
            }
        }

        return $apartments
            ->filter(fn (Apartment $apartment): bool => trim((string) $apartment->apartment_id) === $roomTypeId)
            ->values();
    }

    private function extractRoomBlocks(mixed $payload): array
    {
        $blocks = [];
        $walk = function (mixed $value) use (&$walk, &$blocks): void {
            if (! is_array($value)) {
                return;
            }

            if (isset($value['roomBlockID']) || isset($value['roomBlockId'])) {
                $blocks[] = $value;
                return;
            }

            foreach ($value as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($payload);

        return $blocks;
    }

    private function requiredWebhookEvents(): array
    {
        return [
            ['roomblock', 'created'],
            ['roomblock', 'removed'],
            ['roomblock', 'details_changed'],
            ['availability', 'closeout_changed'],
        ];
    }

    private function humanBlockType(string $type): string
    {
        return match ($type) {
            'out_of_service' => 'Out of service',
            'courtesy_hold' => 'Courtesy hold',
            default => 'Blocked dates',
        };
    }

    private function propertyId(): string
    {
        $configured = trim((string) config('cloudbeds.api.property_id'));
        if ($configured !== '') {
            return $configured;
        }

        $propertyId = collect($this->cloudbeds->roomTypes())
            ->pluck('property_id')
            ->map(fn ($id): string => trim((string) $id))
            ->first(fn (string $id): bool => $id !== '');

        if (! $propertyId) {
            throw new RuntimeException('Cloudbeds property ID could not be determined.');
        }

        return $propertyId;
    }

    private function apiRequest()
    {
        $apiKey = trim((string) config('cloudbeds.api.key'));
        if ($apiKey === '') {
            throw new RuntimeException('Cloudbeds API key is not configured.');
        }

        return Http::acceptJson()
            ->withHeaders(['x-api-key' => $apiKey])
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300);
    }

    private function webhookRequest()
    {
        return $this->apiRequest();
    }

    private function apiBaseUrl(): string
    {
        return rtrim((string) config('cloudbeds.api.base_url', 'https://api.cloudbeds.com/api/v1.3'), '/');
    }

    private function webhookBaseUrl(): string
    {
        return rtrim((string) config('cloudbeds.webhooks.base_url', 'https://api.cloudbeds.com/api/v1.2'), '/');
    }

    private function webhookSecret(): string
    {
        $configured = trim((string) config('cloudbeds.webhooks.secret'));
        if ($configured !== '') {
            return $configured;
        }

        $appKey = (string) config('app.key');
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is required to secure the Cloudbeds webhook.');
        }

        return hash_hmac('sha256', 'maisonbe-cloudbeds-availability-webhook', $appKey);
    }
}
