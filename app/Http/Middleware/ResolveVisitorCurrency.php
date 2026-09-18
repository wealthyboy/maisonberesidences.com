<?php

namespace App\Http\Middleware;

use App\Services\CurrencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveVisitorCurrency
{
    public function __construct(private readonly CurrencyService $currencies) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestedCurrency = strtoupper((string) $request->query('currency'));

        if (in_array($requestedCurrency, ['USD', 'NGN'], true)) {
            $request->session()->put('currency_preference', $requestedCurrency);
        } elseif ($requestedCurrency === 'AUTO') {
            $request->session()->forget(['currency_preference', 'currency', 'currency_auto_resolved']);
        }

        if ($request->is('apartments/*/availability')) {
            $context = $request->session()->get('currency', [
                'code' => 'USD',
                'symbol' => '$',
                'rate' => 1.0,
                'country' => null,
            ]);

            $request->attributes->set('currency', $context);

            return $next($request);
        }

        $context = $this->currencies->resolveFor($request);
        $request->attributes->set('currency', $context);
        $request->session()->put('currency', $context);

        if (! $request->session()->has('currency_preference')) {
            $request->session()->put('currency_auto_resolved', true);
        }

        return $next($request);
    }
}
