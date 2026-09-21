<?php

namespace App\Support;

use Carbon\CarbonInterface;

class StayRestrictions
{
    public const SEASONAL_BLACKOUT_MESSAGE = 'Apartments are currently unavailable from November 15 through January 31. Please choose another stay.';

    public static function overlapsSeasonalBlackout(?CarbonInterface $checkin, ?CarbonInterface $checkout): bool
    {
        if (! $checkin || ! $checkout) {
            return false;
        }

        for ($year = $checkin->year - 1; $year <= $checkout->year; $year++) {
            $blackoutStart = $checkin->copy()->setDate($year, 11, 15)->startOfDay();
            $blackoutEnd = $checkin->copy()->setDate($year + 1, 1, 31)->endOfDay();

            if ($checkin->lte($blackoutEnd) && $checkout->gte($blackoutStart)) {
                return true;
            }
        }

        return false;
    }
}
