<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Services\ApartmentQuoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ApartmentSearchController extends Controller
{
    public function __construct(private readonly ApartmentQuoteService $quotes) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'checkin' => ['nullable', 'date'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $checkin = filled($filters['checkin'] ?? null) ? Carbon::parse($filters['checkin'])->startOfDay() : null;
        $checkout = filled($filters['checkout'] ?? null) ? Carbon::parse($filters['checkout'])->startOfDay() : null;
        $currency = $request->attributes->get('currency');

        $apartments = Apartment::query()
            ->with(['images', 'property'])
            ->when(
                filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null),
                function ($query) use ($checkin, $checkout) {
                    $query->whereDoesntHave('invoiceItems', function ($invoiceItems) use ($checkin, $checkout) {
                        $invoiceItems
                            ->whereHas('invoice', fn ($invoice) => $invoice->where('payment_status', 'paid'))
                            ->whereNotNull('checkin')
                            ->whereNotNull('checkout')
                            ->where('checkin', '<', $checkout)
                            ->where('checkout', '>', $checkin);
                    });
                }
            )
            ->when(
                filled($filters['guests'] ?? null),
                fn ($query) => $query->where('max_adults', '>=', $filters['guests'])
            )
            ->when(
                filled($filters['rooms'] ?? null) && (int) $filters['rooms'] > 1,
                fn ($query) => $query->where('no_of_rooms', '>=', $filters['rooms'])
            )
            ->orderBy('name')
            ->paginate(6)
            ->withQueryString();

        $apartments->getCollection()->each(function (Apartment $apartment) use ($checkin, $checkout, $currency): void {
            $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));
        });

        if ($request->ajax()) {
            return view('apartments.partials.results', compact('apartments', 'filters', 'currency'));
        }

        return view('apartments.index', compact('apartments', 'filters', 'currency'));
    }

    public function show(Request $request, Apartment $apartment): View
    {
        $filters = $request->validate([
            'checkin' => ['nullable', 'date'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);
        $checkin = filled($filters['checkin'] ?? null) ? Carbon::parse($filters['checkin'])->startOfDay() : null;
        $checkout = filled($filters['checkout'] ?? null) ? Carbon::parse($filters['checkout'])->startOfDay() : null;
        $currency = $request->attributes->get('currency');

        $apartment->load(['images', 'property']);
        $apartment->setAttribute('stay_quote', $this->quotes->quote($apartment, $checkin, $checkout, $currency));

        return view('apartments.show', compact('apartment', 'filters', 'currency'));
    }

    public function availability(Request $request, Apartment $apartment): JsonResponse
    {
        $data = $request->validate([
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $checkin = Carbon::parse($data['checkin'])->startOfDay();
        $checkout = Carbon::parse($data['checkout'])->startOfDay();
        $available = (! isset($data['guests']) || $apartment->max_adults >= $data['guests'])
            && ! $apartment->invoiceItems()
                ->whereHas('invoice', fn ($invoice) => $invoice->where('payment_status', 'paid'))
                ->where('checkin', '<', $checkout)
                ->where('checkout', '>', $checkin)
                ->exists();

        return response()->json([
            'available' => $available,
            'message' => $available ? 'This apartment is available for your chosen stay.' : 'This apartment is not available for the selected stay.',
            'reserve_url' => $available ? route('reservations.create', $apartment).'?'.http_build_query($data) : null,
        ]);
    }
}
