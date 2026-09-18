<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stevebauman\Location\Facades\Location;

class CurrencyService
{
    private const FALLBACK_USD_NGN_RATE = 1500.00;

    private const CACHE_KEY = 'currency.usd-ngn';

    public function resolveFor(Request $request): array
    {
        $requestedCurrency = strtoupper((string) $request->query('currency'));

        if (in_array($requestedCurrency, ['USD', 'NGN'], true)) {
            return $this->context($requestedCurrency, 'Manual selection');
        }

        $preferredCurrency = strtoupper((string) $request->session()->get('currency_preference'));

        if (in_array($preferredCurrency, ['USD', 'NGN'], true)) {
            return $this->context($preferredCurrency, 'Manual selection');
        }

        $resolvedCurrency = $request->session()->get('currency');

        if (
            $request->session()->get('currency_auto_resolved') === true
            && is_array($resolvedCurrency)
            && in_array(strtoupper((string) ($resolvedCurrency['code'] ?? '')), ['USD', 'NGN'], true)
        ) {
            return $resolvedCurrency;
        }

        $countryCode = $this->countryCodeFromHeaders($request);

        if ($countryCode !== '') {
            return $this->context($countryCode === 'NG' ? 'NGN' : 'USD', $countryCode);
        }

        $visitorIp = $this->visitorIp($request);

        // Private and reserved addresses cannot be geolocated meaningfully.
        if (! filter_var($visitorIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $this->context('USD', 'Local network');
        }

        try {
            $position = Location::get($visitorIp);
            $countryCode = strtoupper((string) ($position?->countryCode ?? ''));

            return $this->context($countryCode === 'NG' ? 'NGN' : 'USD', $position?->countryName);
        } catch (\Throwable $exception) {
            Log::debug('Visitor currency location lookup failed.', ['message' => $exception->getMessage()]);

            return $this->context('USD');
        }
    }

    private function countryCodeFromHeaders(Request $request): string
    {
        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Appengine-Country', 'X-Country-Code', 'X-Geo-Country'] as $header) {
            $countryCode = strtoupper((string) $request->header($header));

            if (preg_match('/^[A-Z]{2}$/', $countryCode) && $countryCode !== 'XX') {
                return $countryCode;
            }
        }

        return '';
    }

    private function visitorIp(Request $request): string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $ip = trim((string) $request->header($header));

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        foreach (explode(',', (string) $request->header('X-Forwarded-For')) as $ip) {
            $ip = trim($ip);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return (string) $request->ip();
    }

    public function convertFromUsd(float $amount, array $currency): float
    {
        return round($amount * (float) $currency['rate'], 2);
    }

    public function format(float $amount, array $currency): string
    {
        $decimals = $currency['code'] === 'NGN'
            ? 0
            : (abs($amount - round($amount)) < 0.005 ? 0 : 2);

        return $currency['symbol'].number_format($amount, $decimals);
    }

    public function paystackCurrency(): array
    {
        return [
            'code' => 'NGN',
            'symbol' => '₦',
            'rate' => $this->effectiveUsdToNgnRate(),
            'country' => 'Paystack settlement',
        ];
    }

    public function rateSnapshot(): array
    {
        $liveRate = $this->liveUsdToNgnRate();
        $adjustment = $this->adjustmentPercent();
        $record = $this->rateRecord();

        return [
            'base_currency' => 'USD',
            'quote_currency' => 'NGN',
            'live_rate' => $liveRate,
            'adjustment_percent' => $adjustment,
            'effective_rate' => $this->applyAdjustment($liveRate, $adjustment),
            'retrieved_at' => $record->retrieved_at,
            'source' => 'ExchangeRate-API',
        ];
    }

    public function updateAdjustment(float $percent): array
    {
        if (Schema::hasColumn('currency_rates', 'adjustment_percent')) {
            $this->rateRecord()->update(['adjustment_percent' => $percent]);
        }

        return $this->rateSnapshot();
    }

    public function refreshLiveUsdToNgnRate(): bool
    {
        $rate = $this->fetchLiveUsdToNgnRate();

        if ($rate === null) {
            return false;
        }

        Cache::put(self::CACHE_KEY, $rate, now()->addHours(6));

        return true;
    }

    private function context(string $code, ?string $country = null): array
    {
        $rate = $code === 'NGN' ? $this->effectiveUsdToNgnRate() : 1.0;

        return [
            'code' => $code,
            'symbol' => $code === 'NGN' ? '₦' : '$',
            'rate' => $rate,
            'country' => $country,
        ];
    }

    private function effectiveUsdToNgnRate(): float
    {
        $liveRate = $this->liveUsdToNgnRate();

        return $this->applyAdjustment($liveRate, $this->adjustmentPercent());
    }

    private function liveUsdToNgnRate(): float
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function (): float {
            return $this->fetchLiveUsdToNgnRate() ?: $this->storedUsdToNgnRate();
        });
    }

    private function fetchLiveUsdToNgnRate(): ?float
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://api.exchangerate-api.com/v4/latest/USD');
            $rate = (float) data_get($response->json(), 'rates.NGN');

            if ($response->successful() && $rate > 0) {
                $this->rateRecord()->update([
                    'rate' => $rate,
                    'retrieved_at' => now(),
                ]);

                return $rate;
            }
        } catch (\Throwable $exception) {
            Log::warning('USD to NGN rate refresh failed.', ['message' => $exception->getMessage()]);
        }

        return null;
    }

    private function rateRecord(): CurrencyRate
    {
        return CurrencyRate::query()->firstOrCreate(
            ['base_currency' => 'USD', 'quote_currency' => 'NGN'],
            ['rate' => self::FALLBACK_USD_NGN_RATE, 'retrieved_at' => null]
        );
    }

    private function storedUsdToNgnRate(): float
    {
        $rate = (float) $this->rateRecord()->rate;

        return $rate > 0 ? $rate : self::FALLBACK_USD_NGN_RATE;
    }

    private function adjustmentPercent(): float
    {
        if (! Schema::hasColumn('currency_rates', 'adjustment_percent')) {
            return 0.0;
        }

        return (float) ($this->rateRecord()->adjustment_percent ?? 0);
    }

    private function applyAdjustment(float $rate, float $adjustmentPercent): float
    {
        return round(max(0.01, $rate * (1 + ($adjustmentPercent / 100))), 6);
    }
}
