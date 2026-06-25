@if (filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null))
    <p class="results-notice">We found {{ $apartments->total() }} {{ \Illuminate\Support\Str::plural('apartment', $apartments->total()) }} for your selected stay.</p>
@endif
@if (session('booking_error'))<p class="results-notice results-notice-error">{{ session('booking_error') }}</p>@endif
@if ($apartments->count() === 0)
    <p class="results-empty">There are no residences available for these dates. Please choose another stay.</p>
@else
    <div class="results-grid residence-grid">
        @foreach ($apartments as $apartment)
            <x-apartment-card :apartment="$apartment" :quote="$apartment->stay_quote" :filters="$filters" />
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
