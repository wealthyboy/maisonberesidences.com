<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Image;
use App\Rules\MinimumStay;
use App\Services\ApartmentQuoteService;
use App\Support\StayRestrictions;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApartmentSearchController extends Controller
{
    public function __construct(private readonly ApartmentQuoteService $quotes) {}

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
        $hasStayDates = $checkin !== null && $checkout !== null;
        $seasonalBlackout = StayRestrictions::overlapsSeasonalBlackout($checkin, $checkout);
        $currency = $request->attributes->get('currency');

        $apartments = Apartment::query()
            ->with(['images', 'property', 'attributes.parent'])
            ->when($hasStayDates, fn ($query) => $query->publiclyAvailable()->availableFor($checkin, $checkout))
            ->when($seasonalBlackout, fn ($query) => $query->whereRaw('1 = 0'))
            ->when(
                filled($filters['guests'] ?? null),
                fn ($query) => $query->where('max_adults', '>=', $filters['guests'])
            )
            ->when(
                filled($filters['rooms'] ?? null) && (int) $filters['rooms'] > 1,
                fn ($query) => $query->where('no_of_rooms', '>=', $filters['rooms'])
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $apartments->getCollection()->each(function (Apartment $apartment) use ($checkin, $checkout, $currency): void {
            $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));
        });

        if ($request->ajax()) {
            return view('apartments.partials.results', compact('apartments', 'filters', 'currency'));
        }

        $menuImage = Image::query()
            ->where('imageable_type', Apartment::class)
            ->whereIn('imageable_id', Apartment::query()->publiclyAvailable()->select('id'))
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->inRandomOrder()
            ->value('image');

        return view('apartments.index', compact('apartments', 'filters', 'currency', 'menuImage'));
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

    private function inventoryLimits(): array
    {
        return [
            'guests' => max(1, (int) (Apartment::query()->publiclyAvailable()->max('max_adults') ?: 1)),
            'rooms' => max(2, (int) (Apartment::query()->publiclyAvailable()->max('no_of_rooms') ?: 2)),
        ];
    }
}
