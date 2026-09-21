<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumStay implements ValidationRule
{
    public function __construct(
        private readonly mixed $checkin,
        private readonly int $nights = 2,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! filled($this->checkin) || ! filled($value)) {
            return;
        }

        try {
            $minimumCheckout = Carbon::parse($this->checkin)->startOfDay()->addDays($this->nights);
            $checkout = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return;
        }

        if ($checkout->lt($minimumCheckout)) {
            $fail('The minimum stay is '.$this->nights.' nights.');
        }
    }
}
