<?php

namespace App\Services;

use InvalidArgumentException;

class CouponService
{
    public function __construct(private readonly CurrencyService $currencies) {}

    public function apply(?string $code, float $subtotal, array $currency): array
    {
        $normalized = strtoupper(trim((string) $code));

        if ($normalized === '') {
            return $this->emptyResult($subtotal, $currency);
        }

        $coupon = config("booking.coupons.{$normalized}");

        if (! is_array($coupon) || ! ($coupon['active'] ?? true)) {
            throw new InvalidArgumentException('That coupon code is not valid.');
        }

        $type = (string) ($coupon['type'] ?? 'fixed');
        $value = (float) ($coupon['value'] ?? 0);

        if ($value <= 0 || ! in_array($type, ['fixed', 'percent'], true)) {
            throw new InvalidArgumentException('That coupon code is not valid.');
        }

        $discount = $type === 'percent'
            ? $subtotal * min($value, 100) / 100
            : $value;

        $discount = round(min($discount, $subtotal), 2);
        $total = round(max($subtotal - $discount, 0), 2);

        return [
            'code' => $normalized,
            'type' => $type,
            'value' => $value,
            'discount' => $discount,
            'total' => $total,
            'display_discount' => $this->currencies->format($discount, $currency),
            'display_total' => $this->currencies->format($total, $currency),
        ];
    }

    private function emptyResult(float $subtotal, array $currency): array
    {
        return [
            'code' => null,
            'type' => 'fixed',
            'value' => 0.0,
            'discount' => 0.0,
            'total' => round($subtotal, 2),
            'display_discount' => $this->currencies->format(0, $currency),
            'display_total' => $this->currencies->format($subtotal, $currency),
        ];
    }
}
