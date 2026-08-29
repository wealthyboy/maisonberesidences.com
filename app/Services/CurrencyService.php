<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class CurrencyService
{
    private const FALLBACK_USD_NGN_RATE = 1500.00;

    public function resolveFor(Request $request): array
    {
        $requestedCurrency = strtoupper((string) $request->query('currency'));

        if (in_array($requestedCurrency, ['USD', 'NGN'], true)) {
            return $this->context($requestedCurrency, 'manual selection');
        }

        // Localhost cannot be geolocated meaningfully. Avoid blocking the
        // request on an external lookup when developing without internet.
        if (app()->environment('local')) {
            return $this->context('USD', 'Local development');
        }

        $countryCode = $this->countryCodeFromHeaders($request);

        if ($countryCode !== '') {
            return $this->context($countryCode === 'NG' ? 'NGN' : 'USD', $countryCode);
        }

        try {
            $position = Location::get($this->visitorIp($request));
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
        return $currency['symbol'].number_format($amount, $currency['code'] === 'NGN' ? 0 : 2);
    }

    public function paystackCurrency(): array
    {
        return [
            'code' => 'NGN',
            'symbol' => '₦',
            'rate' => $this->storedUsdToNgnRate(),
            'country' => 'Paystack settlement',
        ];
    }

    private function context(string $code, ?string $country = null): array
    {
        $rate = $code === 'NGN' ? $this->usdToNgnRate() : 1.0;

        return [
            'code' => $code,
            'symbol' => $code === 'NGN' ? '₦' : '$',
            'rate' => $rate,
            'country' => $country,
        ];
    }

    private function usdToNgnRate(): float
    {
        return Cache::remember('currency.usd-ngn', now()->addHours(6), function (): float {
            $fallback = $this->storedUsdToNgnRate();

            try {
                $response = Http::timeout(5)->get('https://api.exchangerate-api.com/v4/latest/USD');
                $rate = (float) data_get($response->json(), 'rates.NGN');

                if ($response->successful() && $rate > 0) {
                    CurrencyRate::query()->updateOrCreate(
                        ['base_currency' => 'USD', 'quote_currency' => 'NGN'],
                        ['rate' => $rate, 'retrieved_at' => now()]
                    );

                    return $rate;
                }
            } catch (\Throwable $exception) {
                Log::warning('USD to NGN rate refresh failed.', ['message' => $exception->getMessage()]);
            }

            return $fallback;
        });
    }

    private function storedUsdToNgnRate(): float
    {
        return (float) (CurrencyRate::query()
            ->where('base_currency', 'USD')
            ->where('quote_currency', 'NGN')
            ->value('rate') ?: self::FALLBACK_USD_NGN_RATE);
    }
}
