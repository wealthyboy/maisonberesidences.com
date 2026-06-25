<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Information;
use App\Models\SystemSetting;
use App\Services\ApartmentQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly ApartmentQuoteService $quotes) {}

    public function __invoke(Request $request): View
    {
        if (! $request->boolean('live')) {
            return view('welcome');
        }

        $currency = $request->attributes->get('currency');
        $information = Schema::hasTable('information')
            ? Information::query()->orderBy('sort_order')->orderBy('title')->get()
            : collect();
        $settings = Schema::hasTable('system_settings') ? SystemSetting::query()->first() : null;

        if (! Schema::hasTable('apartments')) {
            return view('home', compact('information', 'settings') + ['apartments' => collect()]);
        }

        $apartments = Apartment::query()
            ->with('images')
            ->orderBy('id')
            ->limit(4)
            ->get();

        $apartments->each(function (Apartment $apartment) use ($currency): void {
            $apartment->setAttribute('home_quote', $this->quotes->quote($apartment, null, null, $currency));
        });

        return view('home', compact('apartments', 'information', 'settings'));
    }
}
