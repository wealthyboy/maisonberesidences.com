<?php

namespace App\Http\Controllers;

use App\Models\AdminModuleRecord;
use App\Models\Apartment;
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
                ->whereHas('video', function ($query): void {
                    $query->where('encoded', true)->where('status', 'ready');
                })
                ->orderByDesc('published_at')
                ->latest('id')
                ->first()
            : null;
        $heroImages = collect();

        if (Schema::hasTable('apartments') && Schema::hasTable('images')) {
            $firstApartmentId = Apartment::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');

            if ($firstApartmentId) {
                $heroImages = Image::query()
                    ->where('imageable_type', Apartment::class)
                    ->where('imageable_id', $firstApartmentId)
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->where(function ($query): void {
                        $query->whereRaw('LOWER(caption) LIKE ?', ['%living room%'])
                            ->orWhereRaw('LOWER(caption) LIKE ?', ['%livingroom%'])
                            ->orWhereRaw('LOWER(caption) LIKE ?', ['%lounge%']);
                    })
                    ->inRandomOrder()
                    ->limit(4)
                    ->get();

                if ($heroImages->count() < 4) {
                    $fillImages = Image::query()
                        ->where('imageable_type', Apartment::class)
                        ->where('imageable_id', $firstApartmentId)
                        ->whereNotNull('image')
                        ->where('image', '!=', '')
                        ->whereNotIn('id', $heroImages->pluck('id'))
                        ->inRandomOrder()
                        ->limit(4 - $heroImages->count())
                        ->get();

                    $heroImages = $heroImages->concat($fillImages)->values();
                }
            }
        }
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
            return view('home', compact('information', 'settings', 'currency', 'bedroomImages', 'heroBanner', 'heroImages') + ['apartments' => collect()]);
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

        return view('home', compact('apartments', 'information', 'settings', 'currency', 'bedroomImages', 'heroBanner', 'heroImages'));
    }
}
