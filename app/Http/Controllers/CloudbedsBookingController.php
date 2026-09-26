<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CloudbedsBookingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $apartmentGalleries = Apartment::query()
            ->with('images')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (Apartment $apartment): array {
                $images = $apartment->images
                    ->map(fn ($image): array => [
                        'url' => $this->imageUrl($image->image),
                        'caption' => trim((string) $image->caption),
                    ])
                    ->filter(fn (array $image): bool => filled($image['url']))
                    ->values();

                if ($images->isEmpty() && filled($apartment->image)) {
                    $images->push([
                        'url' => $this->imageUrl($apartment->image),
                        'caption' => '',
                    ]);
                }

                return [
                    'name' => $apartment->name,
                    'images' => $images,
                ];
            })
            ->filter(fn (array $apartment): bool => filled($apartment['name']) && count($apartment['images']) > 0)
            ->values();

        return view('booking.cloudbeds', compact('apartmentGalleries'));
    }

    private function imageUrl(?string $path): ?string
    {
        if (! filled($path)) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) $path = substr($path, 7);

        return asset($path);
    }
}
