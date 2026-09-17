<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyRateController extends Controller
{
    public function index(CurrencyService $currencies): View
    {
        return view('admin.currency-rates.index', [
            'title' => 'Currency Rates',
            'snapshot' => $currencies->rateSnapshot(),
        ]);
    }

    public function update(Request $request, CurrencyService $currencies): RedirectResponse
    {
        $data = $request->validate([
            'adjustment_percent' => ['required', 'numeric', 'min:-99', 'max:1000'],
        ]);

        $currencies->updateAdjustment((float) $data['adjustment_percent']);

        return back()->with('status', 'Currency adjustment updated.');
    }

    public function refresh(CurrencyService $currencies): RedirectResponse
    {
        if (! $currencies->refreshLiveUsdToNgnRate()) {
            return back()->with('error', 'The live rate could not be refreshed. The last saved rate is still being used.');
        }

        return back()->with('status', 'Live USD to NGN rate refreshed.');
    }
}
