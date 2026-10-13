<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\PeakPeriod;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ApartmentQuoteService
{
    private array $peakPeriodSnapshots = [];

    public function __construct(
        private readonly CurrencyService $currencies,
        private readonly PromotionService $promotions,
        private readonly CloudbedsPricingService $cloudbedsPrices,
    ) {}

    public function quote(Apartment $apartment, ?Carbon $checkin, ?Carbon $checkout, array $currency, int $guests = 1): array
    {
        if ($this->cloudbedsPrices->enabled()) {
            return $this->cloudbedsQuote($apartment, $checkin, $checkout, $currency, $guests);
        }
        $promotion = $this->promotions->activeFor($apartment);
        $baseNightlyUsd = $this->baseNightlyUsd($apartment, $promotion === null);
        $nights = $checkin && $checkout ? max($checkin->diffInDays($checkout), 1) : 1;
        $peakNights = 0;
        $peakExtraUsd = 0.0;
        $ngnRate = null;

        if ($checkin && $checkout) {
            $hasActiveFlag = Schema::hasColumn('peak_periods', 'is_active');
            $increaseColumn = Schema::hasColumn('peak_periods', 'increase_percent')
                ? 'increase_percent'
                : 'discount';

            $periods = PeakPeriod::query()
                ->when($hasActiveFlag, fn ($query) => $query->where('is_active', true))
                ->whereDate('start_date', '<', $checkout)
                ->whereDate('end_date', '>=', $checkin)
                ->get();

            foreach (CarbonPeriod::create($checkin, '1 day', $checkout->copy()->subDay()) as $night) {
                $nightPeriods = $periods
                    ->filter(fn (PeakPeriod $period): bool => $night->betweenIncluded($period->start_date, $period->end_date));
                $specificPrices = [];

                foreach ($nightPeriods as $period) {
                    $price = ($period->apartment_prices ?? [])[$apartment->id] ?? null;
                    if ($price === null) {
                        continue;
                    }

                    $priceUsd = (float) $price;
                    if ($period->price_currency === 'NGN') {
                        $ngnRate ??= ($currency['code'] ?? 'USD') === 'NGN'
                            ? (float) $currency['rate']
                            : (float) $this->currencies->paystackCurrency()['rate'];
                        $priceUsd /= max(0.000001, $ngnRate);
                    }
                    $specificPrices[] = $priceUsd;
                }

                if ($specificPrices !== []) {
                    // A fixed apartment rate replaces, rather than stacks on,
                    // percentage increases. Keep the existing highest-rate
                    // behaviour when more than one period covers this night.
                    $peakNights++;
                    $peakExtraUsd += max($specificPrices) - $baseNightlyUsd;

                    continue;
                }

                $increase = (float) $nightPeriods->max($increaseColumn);

                if ($increase > 0) {
                    $peakNights++;
                    $peakExtraUsd += $baseNightlyUsd * ($increase / 100);
                }
            }
        }

        $totalUsd = ($baseNightlyUsd * $nights) + $peakExtraUsd;
        // Use the stay's average nightly rate so displayed prices and checkout
        // totals agree when a stay contains both regular and peak nights.
        $nightly = $this->currencies->convertFromUsd($totalUsd / $nights, $currency);
        $total = $this->currencies->convertFromUsd($totalUsd, $currency);

        $quote = [
            'currency' => $currency,
            'nights' => $nights,
            'peak_nights' => $peakNights,
            'nightly' => $nightly,
            'total' => $total,
            'total_usd' => $totalUsd,
            'display_nightly' => $this->currencies->format($nightly, $currency),
            'display_total' => $this->currencies->format($total, $currency),
            'rate_source' => 'local',
            'price_available' => true,
        ];

        return $this->promotions->applyToQuote($apartment, $quote, $promotion);
    }

    private function cloudbedsQuote(Apartment $apartment, ?Carbon $checkin, ?Carbon $checkout, array $currency, int $guests): array
    {
        $nights = $checkin && $checkout ? max(1, (int) $checkin->diffInDays($checkout)) : 1;
        $rate = null;
        $breakdown = [];
        $ngnRate = null;
        $toUsd = function (float $amount, string $sourceCurrency) use ($currency, &$ngnRate): float {
            if ($sourceCurrency === 'USD') {
                return $amount;
            }
            if ($sourceCurrency !== 'NGN') {
                throw new RuntimeException('An override price currency must be USD or NGN.');
            }
            $ngnRate ??= ($currency['code'] ?? 'USD') === 'NGN' ? (float) $currency['rate'] : (float) $this->currencies->paystackCurrency()['rate'];

            return $amount / max(0.000001, $ngnRate);
        };

        if ($checkin && $checkout) {
            $key = $checkin->toDateString().'|'.$checkout->toDateString();
            $periods = $this->peakPeriodSnapshots[$key] ??= PeakPeriod::query()->where('is_active', true)
                ->whereDate('start_date', '<', $checkout)->whereDate('end_date', '>=', $checkin)->get();
            foreach (CarbonPeriod::create($checkin, '1 day', $checkout->copy()->subDay()) as $night) {
                $matching = $periods->filter(fn (PeakPeriod $period): bool => $night->betweenIncluded($period->start_date, $period->end_date));
                $fixed = $matching->filter(fn (PeakPeriod $period): bool => isset(($period->apartment_prices ?? [])[$apartment->id]));
                $entry = ['date' => $night->toDateString(), 'source' => 'cloudbeds', 'increase' => 0.0];
                if ($fixed->isNotEmpty()) {
                    $entry['source'] = 'peak_override';
                    $entry['nightly_usd'] = (float) $fixed->map(function (PeakPeriod $period) use ($apartment, $toUsd): float {
                        $price = $period->apartment_prices[$apartment->id];
                        if (! is_numeric($price) || ! is_finite((float) $price) || (float) $price <= 0) {
                            throw new RuntimeException('An active peak override requires a positive nightly price.');
                        }

                        return $toUsd((float) $price, $period->price_currency ?? 'USD');
                    })->max();
                } else {
                    $entry['increase'] = max(0.0, (float) $matching->max('increase_percent'));
                    if ($apartment->price_override_active) {
                        if (! is_numeric($apartment->price) || (float) $apartment->price <= 0) {
                            throw new RuntimeException('An active site override requires a positive site nightly rate.');
                        }
                        $entry['source'] = 'site_override';
                        $entry['nightly_usd'] = (float) $apartment->price;
                    }
                }
                $breakdown[] = $entry;
            }
        }

        $needsCloudbeds = collect($breakdown)->contains('source', 'cloudbeds');
        if ($needsCloudbeds) {
            $rate = $this->cloudbedsPrices->forApartment($apartment, $checkin, $checkout, $guests);
        }
        $hasAdjustments = collect($breakdown)->contains(fn (array $day): bool => $day['source'] !== 'cloudbeds' || $day['increase'] > 0);
        $totalUsd = $rate ? $toUsd($rate['total'], $rate['currency']) : 0.0;
        if ($hasAdjustments) {
            if ($needsCloudbeds) {
                $daily = $rate['nightly_rates'] ?? [];
                if (count($daily) !== $nights || abs(array_sum($daily) - $rate['total']) > max(0.01, $nights * 0.01)) {
                    throw new RuntimeException('Cloudbeds daily rates could not be confirmed for this mixed-price stay. Please try again.');
                }
            }
            $totalUsd = 0.0;
            foreach ($breakdown as &$day) {
                if ($day['source'] === 'cloudbeds') {
                    if (! array_key_exists($day['date'], $rate['nightly_rates'])) {
                        throw new RuntimeException('Cloudbeds daily rates are missing for a night of this stay.');
                    }
                    $day['nightly_usd'] = $toUsd($rate['nightly_rates'][$day['date']], $rate['currency']);
                }
                $day['nightly_usd'] *= 1 + $day['increase'] / 100;
                if ($day['increase'] > 0) {
                    $day['source'] = 'peak_override';
                }
                unset($day['increase']);
                $totalUsd += $day['nightly_usd'];
            }
            unset($day);
        } else {
            // The unmodified provider total remains authoritative, including
            // occupancy supplements, even if no daily breakdown was supplied.
            $breakdown = [];
        }
        $sources = $breakdown ? array_values(array_unique(array_column($breakdown, 'source'))) : ['cloudbeds'];
        $rateSource = count($sources) === 1 ? $sources[0] : 'mixed_override';
        $nightly = $this->currencies->convertFromUsd($totalUsd / $nights, $currency);
        $total = $this->currencies->convertFromUsd($totalUsd, $currency);
        $quote = [
            'currency' => $currency,
            'nights' => $nights,
            'peak_nights' => count(array_filter($breakdown, fn (array $day): bool => $day['source'] === 'peak_override')),
            'nightly' => $nightly,
            'total' => $total,
            'total_usd' => $totalUsd,
            'display_nightly' => $this->currencies->format($nightly, $currency),
            'display_total' => $this->currencies->format($total, $currency),
            'rate_source' => $rateSource,
            'pricing_sources' => $sources,
            'pricing_breakdown' => $breakdown,
            'price_available' => $checkin !== null && $checkout !== null,
            'source_rate_id' => $rate['rate_id'] ?? null,
            'source_room_type_id' => $rate['id'] ?? $apartment->cloudbeds_room_type_id,
            'source_currency' => $rate['currency'] ?? ($breakdown ? 'USD' : null),
        ];

        // One precedence order everywhere: peak dates, active site override,
        // then Cloudbeds. Local Discounts apply once, after rates are resolved.
        return $this->promotions->applyToQuote($apartment, $quote);
    }

    private function baseNightlyUsd(Apartment $apartment, bool $allowLegacySale = true): float
    {
        if ($allowLegacySale) {
            $saleIsValid = $apartment->sale_price !== null
                && (float) $apartment->sale_price > 0
                && ($apartment->sale_price_expires === null || $apartment->sale_price_expires->isFuture());

            if ($saleIsValid) {
                return (float) $apartment->sale_price;
            }
        }

        return (float) $apartment->price;
    }
}
