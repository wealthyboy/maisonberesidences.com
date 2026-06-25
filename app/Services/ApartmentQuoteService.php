<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\PeakPeriod;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;

class ApartmentQuoteService
{
    public function __construct(private readonly CurrencyService $currencies) {}

    public function quote(Apartment $apartment, ?Carbon $checkin, ?Carbon $checkout, array $currency): array
    {
        $baseNightlyUsd = $this->baseNightlyUsd($apartment);
        $nights = $checkin && $checkout ? max($checkin->diffInDays($checkout), 1) : 1;
        $peakNights = 0;
        $peakExtraUsd = 0.0;

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
                $increase = (float) $periods
                    ->filter(fn (PeakPeriod $period): bool => $night->betweenIncluded($period->start_date, $period->end_date))
                    ->max($increaseColumn);

                if ($increase > 0) {
                    $peakNights++;
                    $peakExtraUsd += $baseNightlyUsd * ($increase / 100);
                }
            }
        }

        $totalUsd = ($baseNightlyUsd * $nights) + $peakExtraUsd;
        $nightly = $this->currencies->convertFromUsd($baseNightlyUsd, $currency);
        $total = $this->currencies->convertFromUsd($totalUsd, $currency);

        return [
            'currency' => $currency,
            'nights' => $nights,
            'peak_nights' => $peakNights,
            'nightly' => $nightly,
            'total' => $total,
            'total_usd' => $totalUsd,
            'display_nightly' => $this->currencies->format($nightly, $currency),
            'display_total' => $this->currencies->format($total, $currency),
        ];
    }

    private function baseNightlyUsd(Apartment $apartment): float
    {
        $saleIsValid = $apartment->sale_price !== null
            && (float) $apartment->sale_price > 0
            && ($apartment->sale_price_expires === null || $apartment->sale_price_expires->isFuture());

        return (float) ($saleIsValid ? $apartment->sale_price : $apartment->price);
    }
}
