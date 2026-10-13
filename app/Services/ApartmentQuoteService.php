<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\PeakPeriod;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;

class ApartmentQuoteService
{
    public function __construct(
        private readonly CurrencyService $currencies,
        private readonly PromotionService $promotions,
    ) {}

    public function quote(Apartment $apartment, ?Carbon $checkin, ?Carbon $checkout, array $currency): array
    {
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
        ];

        return $this->promotions->applyToQuote($apartment, $quote, $promotion);
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
