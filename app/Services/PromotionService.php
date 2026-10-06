<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PromotionService
{
    private ?Collection $activePromotions = null;

    public function __construct(
        private readonly CurrencyService $currencies,
    ) {}

    public function activeFor(Apartment $apartment): ?Promotion
    {
        if (! Schema::hasTable('promotions')) {
            return null;
        }

        $promotions = $this->activePromotions();

        $specific = $promotions
            ->where('scope', 'apartment')
            ->first(fn (Promotion $promotion): bool => (int) $promotion->apartment_id === (int) $apartment->id);

        if ($specific) {
            return $specific;
        }

        return $promotions->firstWhere('scope', 'global');
    }

    public function applyToQuote(Apartment $apartment, array $quote, ?Promotion $promotion = null): array
    {
        $promotion ??= $this->activeFor($apartment);

        $quote['has_promotion'] = false;
        $quote['promotion'] = null;
        $quote['original_nightly'] = $quote['nightly'];
        $quote['original_total'] = $quote['total'];
        $quote['original_total_usd'] = $quote['total_usd'];
        $quote['display_original_nightly'] = $quote['display_nightly'];
        $quote['display_original_total'] = $quote['display_total'];

        if (! $promotion) {
            return $quote;
        }

        $originalNightly = max(0.0, (float) $quote['nightly']);
        $originalTotal = max(0.0, (float) $quote['total']);
        $originalTotalUsd = max(0.0, (float) $quote['total_usd']);
        $nights = max(1, (int) ($quote['nights'] ?? 1));

        if ($originalTotal <= 0 || $originalNightly <= 0) {
            return $quote;
        }

        $saleNightly = $originalNightly;
        $saleTotal = $originalTotal;
        $saleTotalUsd = $originalTotalUsd;

        if ($promotion->discount_type === 'percent') {
            $percent = min(100.0, max(0.0, (float) $promotion->discount_value));
            $factor = max(0.0, 1 - ($percent / 100));
            $saleNightly = round($originalNightly * $factor, 2);
            $saleTotal = round($originalTotal * $factor, 2);
            $saleTotalUsd = round($originalTotalUsd * $factor, 2);
        } else {
            // Fixed promotions are entered by Maison Be staff in NGN. Convert
            // that final nightly sale price into the visitor/payment currency
            // at quote time so one rule works for both NGN and USD visitors.
            $fixedNightlyNgn = max(0.0, (float) $promotion->discount_value);
            $usdToNgnRate = max(0.000001, (float) data_get($this->currencies->paystackCurrency(), 'rate', 1));
            $fixedNightlyUsd = round($fixedNightlyNgn / $usdToNgnRate, 6);

            $saleNightly = round(
                $this->currencies->convertFromUsd($fixedNightlyUsd, $quote['currency']),
                2
            );
            $saleTotal = round($saleNightly * $nights, 2);
            $saleTotalUsd = round($fixedNightlyUsd * $nights, 2);
        }

        // A promotion must genuinely reduce the current live quote. This keeps
        // an outdated fixed sale price from accidentally increasing a rate.
        if ($saleTotal >= $originalTotal || $saleNightly >= $originalNightly) {
            return $quote;
        }

        $percentage = (int) round((($originalTotal - $saleTotal) / $originalTotal) * 100);
        $percentage = min(100, max(1, $percentage));

        $quote['has_promotion'] = true;
        $quote['nightly'] = $saleNightly;
        $quote['total'] = $saleTotal;
        $quote['total_usd'] = $saleTotalUsd;
        $quote['display_nightly'] = $this->format($saleNightly, $quote['currency']);
        $quote['display_total'] = $this->format($saleTotal, $quote['currency']);
        $quote['promotion'] = [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'scope' => $promotion->scope,
            'type' => $promotion->discount_type,
            'value' => (float) $promotion->discount_value,
            'fixed_price_currency' => $promotion->discount_type === 'fixed_price' ? 'NGN' : null,
            'percentage' => $percentage,
            'promo_text' => trim((string) $promotion->promo_text),
        ];

        return $quote;
    }

    private function activePromotions(): Collection
    {
        if ($this->activePromotions !== null) {
            return $this->activePromotions;
        }

        return $this->activePromotions = Promotion::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    private function format(float $amount, array $currency): string
    {
        $decimals = ($currency['code'] ?? 'USD') === 'NGN'
            ? 0
            : (abs($amount - round($amount)) < 0.005 ? 0 : 2);

        return ($currency['symbol'] ?? '$').number_format($amount, $decimals);
    }
}
