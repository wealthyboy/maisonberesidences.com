<?php

namespace App\Support;

use Carbon\CarbonInterface;

class StayRestrictions
{
    public const DECEMBER_MESSAGE = 'Apartments are currently unavailable for dates in December. Please choose another stay.';

    public static function includesDecember(?CarbonInterface $checkin, ?CarbonInterface $checkout): bool
    {
        if (! $checkin || ! $checkout) {
            return false;
        }

        for ($year = $checkin->year; $year <= $checkout->year; $year++) {
            $decemberStart = $checkin->copy()->setDate($year, 12, 1)->startOfDay();
            $decemberEnd = $decemberStart->copy()->endOfMonth()->endOfDay();

            if ($checkin->lte($decemberEnd) && $checkout->gte($decemberStart)) {
                return true;
            }
        }

        return false;
    }
}
