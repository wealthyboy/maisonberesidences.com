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
        $cacheKey = 'cloudbeds.room_types.current';
        $staleKey = 'cloudbeds.room_types.last_success';

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

            $name = trim((string) ($room['roomTypeNameShort'] ?? $room['roomTypeName'] ?? $room['roomName'] ?? $room['name'] ?? ''));
            $id = trim((string) ($room['roomTypeID'] ?? $room['roomTypeId'] ?? $room['room_type_id'] ?? ''));

            if ($name === '') {
                continue;
            }

            $identity = $id !== '' ? 'id:'.$id : 'name:'.$this->normalizeName($name);
            $maxGuests = max(0, (int) ($room['maxGuests'] ?? $room['max_guests'] ?? 0));

            if (! isset($types[$identity])) {
                $types[$identity] = [
                    'id' => $id,
                    'name' => $name,
                    'short_name' => trim((string) ($room['roomTypeNameShort'] ?? '')),
                    'description' => trim((string) ($room['roomDescription'] ?? $room['description'] ?? '')),
                    'max_guests' => $maxGuests,
                ];
                continue;
            }

            $types[$identity]['max_guests'] = max($types[$identity]['max_guests'], $maxGuests);
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

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }
}
