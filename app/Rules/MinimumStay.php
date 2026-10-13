<?php

namespace App\Rules;

use App\Models\Apartment;
use App\Services\MinimumStayService;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumStay implements ValidationRule
{
    public function __construct(
        private readonly mixed $checkin,
        private readonly int $nights = 1,
        private readonly ?Apartment $apartment = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! filled($this->checkin) || ! filled($value)) {
            return;
        }

        try {
            $checkin = Carbon::parse($this->checkin)->startOfDay();
            $checkout = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return;
        }

        $nights = max($this->nights, app(MinimumStayService::class)->minimumNightsFor($checkin, $checkout, $this->apartment));
        if ($checkout->lt($checkin->copy()->addDays($nights))) {
            $fail('The minimum stay for these dates is '.$nights.' '.str('night')->plural($nights).'.');
        }
    }
}
