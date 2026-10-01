
@if (session('booking_error'))<p class="results-notice results-notice-error">{{ session('booking_error') }}</p>@endif
@if ($apartments->count() === 0)
    <p class="results-empty">There are no residences available to display right now.</p>
@else
    <div class="results-grid residence-grid">
        @foreach ($apartments as $apartment)
            <x-apartment-card
                :apartment="$apartment"
                :quote="$apartment->stay_quote"
                :filters="$filters"
                link-url="#apartment-availability"
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
