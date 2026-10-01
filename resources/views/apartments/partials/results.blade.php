@php
    $cloudbedsQuery = [
        'currency' => strtoupper($currency['code'] ?? 'USD'),
        'utm_source' => 'maisonbe_website',
        'adults' => max(1, (int) ($filters['guests'] ?? 1)),
    ];

    if (filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null)) {
        $cloudbedsQuery['checkin'] = $filters['checkin'];
        $cloudbedsQuery['checkout'] = $filters['checkout'];
    }

    $cloudbedsAvailabilityUrl = route('booking.cloudbeds', $cloudbedsQuery);
@endphp
@if (filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null))
    <p class="results-notice">We found {{ $apartments->total() }} {{ \Illuminate\Support\Str::plural('apartment', $apartments->total()) }} for your selected stay.</p>
@endif
@if (session('booking_error'))<p class="results-notice results-notice-error">{{ session('booking_error') }}</p>@endif
@if ($apartments->count() === 0)
    <p class="results-empty">There are no residence available for these dates. Please choose another stay.</p>
@else
    <div class="results-grid residence-grid">
        @foreach ($apartments as $apartment)
            <x-apartment-card
                :apartment="$apartment"
                :quote="$apartment->stay_quote"
                :filters="$filters"
                :link-url="$cloudbedsAvailabilityUrl"
                booking-label="Check availability"
                :show-price="false"
            />
        @endforeach
    </div>
    @if ($apartments->hasPages())
        <nav class="results-pagination" aria-label="Apartment pages">
            @if ($apartments->onFirstPage())
                <span aria-disabled="true">Previous</span>
            @else
                <a href="{{ $apartments->previousPageUrl() }}">Previous</a>
            @endif
            <p>Page {{ $apartments->currentPage() }} of {{ $apartments->lastPage() }}</p>
            @if ($apartments->hasMorePages())
                <a href="{{ $apartments->nextPageUrl() }}">Next</a>
            @else
                <span aria-disabled="true">Next</span>
            @endif
        </nav>
    @endif
@endif
