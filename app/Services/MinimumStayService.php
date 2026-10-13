<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\MinimumStayPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MinimumStayService
{
    private ?Collection $periods = null;

    public function minimumNightsFor(CarbonInterface $checkin, CarbonInterface $checkout, ?Apartment $apartment = null): int
    {
        // End dates include their final booked night; checkout itself is not a night.
        return max(1, (int) $this->periodsFor($apartment)
            ->filter(fn (MinimumStayPeriod $period): bool => $period->starts_on->lt($checkout->copy()->startOfDay())
                && $period->ends_on->gte($checkin->copy()->startOfDay()))
            ->max('minimum_nights'));
    }

    public function calendarRulesFor(?Apartment $apartment = null): array
    {
        return $this->periodsFor($apartment)
            ->filter(fn (MinimumStayPeriod $period): bool => $period->ends_on->gte(today()))
            ->map(fn (MinimumStayPeriod $period): array => [
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'minimum_nights' => $period->minimum_nights,
            ])->values()->all();
    }

    private function periodsFor(?Apartment $apartment): Collection
    {
        $this->periods ??= Schema::hasTable('minimum_stay_periods')
            ? MinimumStayPeriod::query()->where('is_active', true)->with('apartments:id')->get()
            : collect();

        return $this->periods->filter(fn (MinimumStayPeriod $period): bool => $period->applies_to_all
            || ($apartment && $period->apartments->contains('id', $apartment->id)));
    }
}
