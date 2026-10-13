<?php

namespace App\Services;

use App\Models\Apartment;
use Carbon\CarbonInterface;
use RuntimeException;

class CloudbedsPricingService
{
    private array $stayRates = [];

    public function __construct(private readonly CloudbedsApiService $cloudbeds) {}

    public function enabled(): bool
    {
        return filled(config('cloudbeds.api.key'));
    }

    public function forApartment(Apartment $apartment, CarbonInterface $checkin, CarbonInterface $checkout, int $adults = 1): array
    {
        $key = $checkin->toDateString().'|'.$checkout->toDateString().'|'.max(1, $adults);
        // This cache lasts only for this quote service/request. Every new
        // search or payment request reads fresh Cloudbeds rates, not old DB prices.
        if (! array_key_exists($key, $this->stayRates)) {
            $property = $this->cloudbeds->pricingProperty();
            $rates = [];
            foreach ($this->cloudbeds->ratePlansForStay($checkin->toDateString(), $checkout->toDateString(), $adults) as $record) {
                if (! is_array($record) || filled($record['ratePlanID'] ?? null) || empty($record['roomTypeID'])) {
                    continue;
                }
                // Cloudbeds totalRate is the WHOLE stay, including extra-guest
                // supplements. Do not multiply it by the number of nights.
                $total = $record['totalRate'] ?? null;
                if (! is_numeric($total) || ! is_finite((float) $total) || (float) $total < 0) {
                    continue;
                }
                $rates[] = [
                    'id' => (string) $record['roomTypeID'],
                    'room_type_ids' => [(string) $record['roomTypeID']],
                    'name' => (string) ($record['roomTypeName'] ?? ''),
                    'full_name' => (string) ($record['roomTypeName'] ?? ''),
                    'short_name' => (string) ($record['roomTypeNameShort'] ?? ''),
                    'rate_id' => (string) ($record['rateID'] ?? ''),
                    'total' => (float) $total,
                    'nightly_rates' => $this->nightlyRates($record, $checkin->toDateString(), $checkout->toDateString()),
                    'currency' => $property['currency'],
                ];
            }
            $this->stayRates[$key] = $rates;
        }
        $rate = $this->cloudbeds->matchApartment($apartment, $this->stayRates[$key]);
        if ($rate === null) {
            throw new RuntimeException('A live Cloudbeds rate is not available for this apartment. Please check its Cloudbeds mapping.');
        }

        return $rate;
    }

    private function nightlyRates(array $record, string $checkin, string $checkout): array
    {
        $rates = [];
        $hasSupplements = ! is_numeric($record['roomRate'] ?? null)
            || abs((float) ($record['totalRate'] ?? 0) - (float) $record['roomRate']) > 0.005;
        foreach ((array) ($record['roomRateDetailed'] ?? $record['detailedRates'] ?? []) as $day) {
            if (! is_array($day)) {
                continue;
            }
            $date = (string) ($day['date'] ?? '');
            $amount = $day['totalRate'] ?? (! $hasSupplements ? ($day['rate'] ?? $day['roomRate'] ?? null) : null);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $date >= $checkin && $date < $checkout
                && is_numeric($amount) && is_finite((float) $amount) && (float) $amount >= 0) {
                if (array_key_exists($date, $rates)) {
                    return [];
                }
                $rates[$date] = (float) $amount;
            }
        }

        return $rates;
    }
}
