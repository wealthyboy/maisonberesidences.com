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

            $localApartments->each(function (Apartment $apartment) use ($checkin, $checkout, $currency): void {
                $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));
            });

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
        $localIndex = [];

        foreach ($localApartments as $apartment) {
            foreach ($this->apartmentMatchKeys($apartment) as $key) {
                if ($key !== '' && ! isset($localIndex[$key])) {
                    $localIndex[$key] = $apartment;
                }
            }
        }

        return $roomTypes
            ->unique(fn (array $roomType): string => $this->roomTypeIdentity($roomType))
            ->map(function (array $roomType) use ($localApartments, $localIndex): array {
                $candidateKeys = $this->roomTypeMatchKeys($roomType);
                $apartment = null;

                foreach ($candidateKeys as $candidate) {
                    if (isset($localIndex[$candidate])) {
                        $apartment = $localIndex[$candidate];
                        break;
                    }
                }

                if (! $apartment) {
                    $apartment = $localApartments->first(function (Apartment $local) use ($candidateKeys): bool {
                        foreach ($this->apartmentMatchKeys($local) as $localKey) {
                            foreach ($candidateKeys as $candidate) {
                                if (strlen($candidate) < 4 || strlen($localKey) < 4) {
                                    continue;
                                }

                                if (str_contains($localKey, $candidate) || str_contains($candidate, $localKey)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    });
                }

                // Cloudbeds exposes the penthouse inconsistently depending on the
                // endpoint: sometimes as "Pen", sometimes as "Penthouse", and
                // sometimes as "BELVEDERE - Penthouse". Its public Maison Be
                // presentation data already lives on the local Belvedere record,
                // so bind those Cloudbeds identities to that record explicitly.
                if (! $apartment && $this->isPenthouseRoomType($roomType)) {
                    $apartment = $localApartments->first(function (Apartment $local): bool {
                        return collect($this->apartmentMatchKeys($local))->contains(function (string $key): bool {
                            return str_contains($key, 'belvedere') || str_contains($key, 'penthouse');
                        });
                    });
                }

                if ($apartment && $this->isPenthouseRoomType($roomType)) {
                    // Do not mutate the original collection model because another
                    // Cloudbeds room type may also map to the same local record.
                    $apartment = clone $apartment;
                    $apartment->setAttribute('name', $this->penthouseDisplayName($roomType));
                }

                return [
                    'cloudbeds' => $roomType,
                    'apartment' => $apartment,
                ];
            })
            ->values();
    }


    private function isPenthouseRoomType(array $roomType): bool
    {
        $keys = collect($this->roomTypeMatchKeys($roomType));

        return $keys->contains(function (string $key): bool {
            return in_array($key, ['pen', 'penthouse', 'belvederepenthouse'], true)
                || str_contains($key, 'penthouse');
        });
    }

    private function penthouseDisplayName(array $roomType): string
    {
        foreach (['full_name', 'name', 'short_name'] as $field) {
            $value = trim((string) ($roomType[$field] ?? ''));

            if ($value !== '' && str_contains(strtolower($value), 'penthouse')) {
                return $value;
            }
        }

        return 'BELVEDERE - Penthouse';
    }

    private function roomTypeMatchKeys(array $roomType): array
    {
        return collect([
            $roomType['name'] ?? null,
            $roomType['full_name'] ?? null,
            $roomType['short_name'] ?? null,
        ])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->flatMap(fn (string $value): array => $this->nameVariants($value))
            ->map(fn (string $value): string => $this->normalizeName($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function apartmentMatchKeys(Apartment $apartment): array
    {
        return collect([
            $apartment->name,
            $apartment->slug,
            $apartment->apartment_id,
        ])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->flatMap(fn (string $value): array => $this->nameVariants($value))
            ->map(fn (string $value): string => $this->normalizeName($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nameVariants(string $value): array
    {
        $value = trim($value);
        $variants = [$value];

        $withoutBrand = preg_replace('/\s+(?:at|@)\s+maison\s*be(?:\s+residences?)?\s*$/i', '', $value) ?? $value;
        $withoutBrand = preg_replace('/\s*[-|:]\s*maison\s*be(?:\s+residences?)?\s*$/i', '', $withoutBrand) ?? $withoutBrand;
        $withoutBrand = preg_replace('/^maison\s*be(?:\s+residences?)?\s*[-|:]\s*/i', '', $withoutBrand) ?? $withoutBrand;

        if (trim($withoutBrand) !== '') {
            $variants[] = trim($withoutBrand);
        }

        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($withoutBrand)) ?: [];
        $ignored = ['at', 'maison', 'be', 'residence', 'residences', 'apartment', 'apartments', 'suite', 'suites', 'room', 'rooms'];
        $core = implode('', array_filter($tokens, fn (string $token): bool => $token !== '' && ! in_array($token, $ignored, true)));

        if ($core !== '') {
            $variants[] = $core;
        }

        // Cloudbeds getRooms returns the Maison Be penthouse as the physical
        // room label "Pen". Treat it as an alias of the public room type so
        // it maps to the existing BELVEDERE - Penthouse apartment record.
        $normalized = $this->normalizeName($withoutBrand);
        if (in_array($normalized, ['pen', 'penthouse', 'belvederepenthouse'], true)) {
            $variants[] = 'BELVEDERE - Penthouse';
            $variants[] = 'Penthouse';
            $variants[] = 'Pen';
        }

        return array_values(array_unique($variants));
    }

    private function roomTypeIdentity(array $roomType): string
    {
        $keys = $this->roomTypeMatchKeys($roomType);

        return $keys[0] ?? ('id:'.(string) ($roomType['id'] ?? ''));
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
