<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Image;
use App\Rules\MinimumStay;
use App\Services\ApartmentQuoteService;
use App\Services\CloudbedsApiService;
use App\Support\StayRestrictions;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ApartmentSearchController extends Controller
{
    public function __construct(
        private readonly ApartmentQuoteService $quotes,
        private readonly CloudbedsApiService $cloudbeds,
    ) {}

    public function index(Request $request): View
    {
        $limits = $this->inventoryLimits();

        $filters = $request->validate([
            'search' => ['nullable', 'boolean'],
            'checkin' => ['nullable', 'required_if:search,1', 'required_with:checkout', 'date', 'after_or_equal:today'],
            'checkout' => ['nullable', 'required_if:search,1', 'required_with:checkin', 'date', 'after:checkin', new MinimumStay($request->input('checkin'))],
            'guests' => ['nullable', 'integer', 'min:1', 'max:'.$limits['guests']],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:'.$limits['rooms']],
        ]);

        $checkin = filled($filters['checkin'] ?? null) ? Carbon::parse($filters['checkin'])->startOfDay() : null;
        $checkout = filled($filters['checkout'] ?? null) ? Carbon::parse($filters['checkout'])->startOfDay() : null;
        $currency = $request->attributes->get('currency');

        // Cloudbeds is the inventory source. Maison Be's local apartment records are
        // retained for the richer presentation layer (photos, amenities, descriptions).
        $localApartments = Apartment::query()
            ->with(['images', 'property', 'attributes.parent'])
            ->publiclyAvailable()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $localApartments->each(function (Apartment $apartment) use ($checkin, $checkout, $currency): void {
            $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));
        });

        $cloudbedsError = null;
        $residences = collect();

        try {
            $roomTypes = collect($this->cloudbeds->roomTypes());
            $residences = $this->combineCloudbedsWithLocalApartments($roomTypes, $localApartments);
        } catch (Throwable $exception) {
            Log::warning('Cloudbeds apartment inventory could not be loaded.', [
                'message' => $exception->getMessage(),
            ]);

            $cloudbedsError = 'Live apartment information is temporarily unavailable. Please refresh the page in a moment.';
        }

        if ($request->ajax()) {
            return view('apartments.partials.results', compact('residences', 'filters', 'currency', 'cloudbedsError'));
        }

        $menuImage = Image::query()
            ->where('imageable_type', Apartment::class)
            ->whereIn('imageable_id', Apartment::query()->publiclyAvailable()->select('id'))
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->inRandomOrder()
            ->value('image');

        return view('apartments.index', compact('residences', 'filters', 'currency', 'menuImage', 'cloudbedsError'));
    }

    public function show(Request $request, Apartment $apartment): View
    {
        $limits = $this->inventoryLimits();

        $filters = $request->validate([
            'checkin' => ['nullable', 'required_with:checkout', 'date', 'after_or_equal:today'],
            'checkout' => ['nullable', 'required_with:checkin', 'date', 'after:checkin', new MinimumStay($request->input('checkin'))],
            'guests' => ['nullable', 'integer', 'min:1', 'max:'.$limits['guests']],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:'.$limits['rooms']],
        ]);
        $checkin = filled($filters['checkin'] ?? null) ? Carbon::parse($filters['checkin'])->startOfDay() : null;
        $checkout = filled($filters['checkout'] ?? null) ? Carbon::parse($filters['checkout'])->startOfDay() : null;
        $currency = $request->attributes->get('currency');

        $apartment->load(['images', 'property', 'attributes.parent']);
        $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));

        return view('apartments.show', compact('apartment', 'filters', 'currency'));
    }

    public function availability(Request $request, Apartment $apartment): JsonResponse
    {
        if (! $apartment->allow) {
            return response()->json([
                'available' => false,
                'message' => 'This apartment is currently unavailable for booking.',
                'reserve_url' => null,
            ]);
        }

        $maxGuests = max(1, (int) ($apartment->max_adults ?: 1));

        $data = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin', new MinimumStay($request->input('checkin'))],
            'guests' => ['nullable', 'integer', 'min:1', 'max:'.$maxGuests],
        ]);
        $checkin = Carbon::parse($data['checkin'])->startOfDay();
        $checkout = Carbon::parse($data['checkout'])->startOfDay();

        if (StayRestrictions::overlapsSeasonalBlackout($checkin, $checkout)) {
            return response()->json([
                'available' => false,
                'message' => StayRestrictions::SEASONAL_BLACKOUT_MESSAGE,
                'reserve_url' => null,
            ]);
        }

        $guestCount = (int) ($data['guests'] ?? 1);
        $hasGuestCapacity = $apartment->max_adults <= 0 || $apartment->max_adults >= $guestCount;
        $available = $hasGuestCapacity && $apartment->isAvailableFor($checkin, $checkout);

        return response()->json([
            'available' => $available,
            'message' => $available ? 'This apartment is available for your chosen stay.' : 'This apartment is not available for the selected stay.',
            'reserve_url' => $available ? route('reservations.create', $apartment).'?'.http_build_query($data) : null,
        ]);
    }

    private function combineCloudbedsWithLocalApartments(Collection $roomTypes, Collection $localApartments): Collection
    {
        $localByName = $localApartments->keyBy(fn (Apartment $apartment): string => $this->normalizeName($apartment->name));

        return $roomTypes
            ->map(function (array $roomType) use ($localApartments, $localByName): array {
                $candidateNames = collect([
                    $roomType['name'] ?? null,
                    $roomType['short_name'] ?? null,
                ])->filter()->map(fn (string $name): string => $this->normalizeName($name))->filter()->unique();

                $apartment = null;

                foreach ($candidateNames as $candidate) {
                    $apartment = $localByName->get($candidate);
                    if ($apartment) {
                        break;
                    }
                }

                if (! $apartment) {
                    $apartment = $localApartments->first(function (Apartment $local) use ($candidateNames): bool {
                        $localName = $this->normalizeName($local->name);

                        return $candidateNames->contains(function (string $candidate) use ($localName): bool {
                            if (strlen($candidate) < 4 || strlen($localName) < 4) {
                                return false;
                            }

                            return str_contains($localName, $candidate) || str_contains($candidate, $localName);
                        });
                    });
                }

                return [
                    'cloudbeds' => $roomType,
                    'apartment' => $apartment,
                ];
            })
            ->values();
    }

    private function normalizeName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/^the\s+/', '', $value) ?? $value;

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function inventoryLimits(): array
    {
        return [
            'guests' => max(1, (int) (Apartment::query()->publiclyAvailable()->max('max_adults') ?: 1)),
            'rooms' => max(2, (int) (Apartment::query()->publiclyAvailable()->max('no_of_rooms') ?: 2)),
        ];
    }
}
