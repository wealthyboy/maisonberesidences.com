<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\AdminModuleRecord;
use App\Models\Image;
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
        $user = $request->user();
        $adminCanPreview = $user && method_exists($user, 'hasAdminAccess') && $user->hasAdminAccess();

        if (! $request->boolean('live') && ! $adminCanPreview) {
            return view('welcome');
        }

        $currency = $request->attributes->get('currency');
        $information = Schema::hasTable('information')
            ? Information::query()->orderBy('sort_order')->orderBy('title')->get()
            : collect();
        $settings = Schema::hasTable('system_settings') ? SystemSetting::query()->first() : null;
        $heroBanner = Schema::hasTable('admin_module_records') && Schema::hasTable('videos')
            ? AdminModuleRecord::query()
                ->with('video')
                ->where('module_slug', 'banners')
                ->where('status', 'active')
                ->whereHas('video')
                ->orderByDesc('published_at')
                ->latest('id')
                ->first()
            : null;
        $bedroomImages = Schema::hasTable('images')
            ? Image::query()
                ->where('imageable_type', Apartment::class)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(caption) LIKE ?', ['%bedroom%'])
                        ->orWhereRaw('LOWER(caption) LIKE ?', ['%bed room%']);
                })
                ->inRandomOrder()
                ->limit(4)
                ->get()
            : collect();

        if (! Schema::hasTable('apartments')) {
            return view('home', compact('information', 'settings', 'currency', 'bedroomImages', 'heroBanner') + ['apartments' => collect()]);
        }

        $apartments = Apartment::query()
            ->with(['images', 'attributes.parent'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get();

        $apartments->each(function (Apartment $apartment) use ($currency): void {
            $apartment->setAttribute('home_quote', $this->quotes->quote($apartment, null, null, $currency));
        });

        return view('home', compact('apartments', 'information', 'settings', 'currency', 'bedroomImages', 'heroBanner'));
    }
}
