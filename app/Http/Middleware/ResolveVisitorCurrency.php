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
        $context = $this->currencies->resolveFor($request);
        $request->attributes->set('currency', $context);
        $request->session()->put('currency', $context);
        return $next($request);
    }
}
