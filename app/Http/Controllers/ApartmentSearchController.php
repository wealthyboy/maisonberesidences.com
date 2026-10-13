<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Image;
use App\Rules\MinimumStay;
use App\Services\ApartmentQuoteService;
use App\Services\CloudbedsApiService;
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

        // When a stay search arrives from the homepage, render the page shell first
        // and let the existing card-frame loader show immediately. The browser then
        // requests the live Cloudbeds inventory over AJAX, avoiding a slow full-page wait.
        $deferResults = ! $request->ajax()
            && $request->boolean('search')
            && $checkin
            && $checkout;

        $cloudbedsError = null;
        $residences = collect();

        if (! $deferResults || $request->ajax()) {
            // Cloudbeds is the inventory source. Maison Be's local apartment records are
            // retained for the richer presentation layer (photos, amenities, descriptions).
            $localApartments = Apartment::query()
                ->with(['images', 'property', 'attributes.parent'])
                ->publiclyAvailable()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            try {
                $roomTypes = $checkin && $checkout
                    ? collect($this->cloudbeds->availableRoomTypes(
                        $checkin,
                        $checkout,
                        max(1, (int) ($filters['rooms'] ?? 1)),
                        max(1, (int) ($filters['guests'] ?? 1)),
                    ))
                    : collect($this->cloudbeds->roomTypes());
                $residences = $this->combineCloudbedsWithLocalApartments($roomTypes, $localApartments);
                foreach ($residences as $index => $residence) {
                    $apartment = $residence['apartment'];
                    try {
                        $quote = $this->quotes->quote($apartment, $checkin, $checkout, $currency, max(1, (int) ($filters['guests'] ?? 1)));
                        $apartment?->setAttribute('stay_quote', $quote);
                        $residences[$index] = [...$residence, 'quote' => $quote];
                    } catch (Throwable $exception) {
                        Log::warning('Cloudbeds price could not be confirmed.', ['apartment_id' => $apartment?->id, 'message' => $exception->getMessage()]);
                        $apartment?->setAttribute('stay_quote', null);
                        $residences[$index] = [...$residence, 'quote' => null];
                    }
                }

                // Cloudbeds remains the live inventory authority, but Maison Be's
                // local admin Date Blocks are an absolute override. If any selected
                // stay night overlaps a local block, do not render that residence.
                if ($checkin && $checkout) {
                    $residences = $residences
                        ->reject(function (array $residence) use ($checkin, $checkout): bool {
                            $apartment = $residence['apartment'] ?? null;

                            return $apartment instanceof Apartment
                                && $apartment->isBlockedFor($checkin, $checkout);
                        })
                        ->values();
                }
            } catch (Throwable $exception) {
                Log::warning('Cloudbeds apartment inventory could not be loaded.', [
                    'message' => $exception->getMessage(),
                ]);

                $cloudbedsError = 'Live apartment information is temporarily unavailable. Please refresh the page in a moment.';
            }
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

        return view('apartments.index', compact('residences', 'filters', 'currency', 'menuImage', 'cloudbedsError', 'deferResults'));
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
        $pricingError = null;
        try {
            $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency, max(1, (int) ($filters['guests'] ?? 1))));
        } catch (Throwable $exception) {
            $apartment->setAttribute('stay_quote', null);
            $pricingError = 'Live prices could not be confirmed. Please try another date or contact Maison Be.';
            Log::warning('Cloudbeds apartment price unavailable.', ['apartment_id' => $apartment->id, 'message' => $exception->getMessage()]);
        }

        return view('apartments.show', compact('apartment', 'filters', 'currency', 'pricingError'));
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

        $guestCount = (int) ($data['guests'] ?? 1);
        $hasGuestCapacity = $apartment->max_adults <= 0 || $apartment->max_adults >= $guestCount;
        $cloudbedsRoomType = null;

        try {
            $cloudbedsRoomType = $hasGuestCapacity
                ? $this->cloudbeds->availableRoomTypeForApartment($apartment, $checkin, $checkout, $guestCount)
                : null;
        } catch (Throwable $exception) {
            Log::warning('Cloudbeds availability check failed.', [
                'apartment_id' => $apartment->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'available' => false,
                'message' => 'Live availability could not be confirmed. Please try again.',
                'reserve_url' => null,
            ], 503);
        }

        $available = $hasGuestCapacity
            && $cloudbedsRoomType !== null
            && $apartment->isAvailableFor($checkin, $checkout);

        return response()->json([
            'available' => $available,
            'message' => $available ? 'This apartment is available for your chosen stay.' : 'This apartment is not available for the selected stay.',
            'reserve_url' => $available ? route('reservations.create', $apartment).'?'.http_build_query($data) : null,
        ]);
    }

    private function combineCloudbedsWithLocalApartments(Collection $roomTypes, Collection $localApartments): Collection
    {
        $matchedIds = [];
        $residences = collect();

        // Availability, quoting and checkout all use the same mapping rules.
        // Local records own every presentation field and the public card order.
        // Reserve explicit ID mappings before name-only matches can claim them.
        foreach ($localApartments->sortBy(fn (Apartment $local): int => filled($local->apartment_id) ? 0 : 1) as $local) {
            $roomType = $this->cloudbeds->matchApartment($local, $roomTypes->all());
            if (! $roomType || blank($roomType['id'] ?? null)) {
                continue;
            }
            $roomTypeId = (string) $roomType['id'];
            if (isset($matchedIds[$roomTypeId])) {
                Log::warning('Multiple local apartments match one Cloudbeds room type.', [
                    'apartment_id' => $local->id, 'room_type_id' => $roomTypeId,
                ]);
                continue;
            }

            $apartment = clone $local;
            // Bind this card's quote to the exact available room, even if the
            // rates endpoint uses a different label. Never persist or rename it.
            $apartment->setAttribute('cloudbeds_room_type_id', $roomTypeId);
            $residences->push(['cloudbeds' => $roomType, 'apartment' => $apartment]);
            $matchedIds[$roomTypeId] = true;
        }

        foreach ($roomTypes as $roomType) {
            if (! isset($matchedIds[(string) ($roomType['id'] ?? '')])) {
                Log::warning('Cloudbeds room type has no published local apartment mapping.', [
                    'room_type_id' => $roomType['id'] ?? null,
                ]);
            }
        }

        // Unmapped API rooms must not replace the uploaded cards with generic
        // photos or amenities. Publish a matching local record before listing it.
        return $residences->sortBy(fn (array $residence): array => [
            (int) $residence['apartment']->sort_order,
            (int) $residence['apartment']->id,
        ])->values();
    }

    private function inventoryLimits(): array
    {
        return [
            'guests' => max(1, (int) (Apartment::query()->publiclyAvailable()->max('max_adults') ?: 1)),
            'rooms' => max(2, (int) (Apartment::query()->publiclyAvailable()->max('no_of_rooms') ?: 2)),
        ];
    }
}
