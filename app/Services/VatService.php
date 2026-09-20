<?php

namespace App\Services;

class VatService
{
    public const RATE = 7.5;

    public function __construct(private readonly CurrencyService $currencies) {}

    public function calculate(float $taxableAccommodationTotal): float
    {
        return round(max($taxableAccommodationTotal, 0) * (self::RATE / 100), 2);
    }

    public function quote(float $taxableAccommodationTotal, array $currency): array
    {
        $amount = $this->calculate($taxableAccommodationTotal);

        return [
            'rate' => self::RATE,
            'amount' => $amount,
            'display_amount' => $this->currencies->format($amount, $currency),
        ];
    }
}
