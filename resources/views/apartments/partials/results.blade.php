@if (session('booking_error'))
    <p class="results-notice results-notice-error">{{ session('booking_error') }}</p>
@endif

@foreach ($minimumStayNotices ?? [] as $notice)
    <p class="results-notice">{{ $notice }}</p>
@endforeach

@if ($cloudbedsError)
    <p class="results-notice results-notice-error">{{ $cloudbedsError }}</p>
@elseif ($residences->isEmpty())
    <p class="results-empty">There are no residences available to display right now.</p>
@else
    <div class="results-grid residence-grid">
        @foreach ($residences as $residence)
            @if ($residence['apartment'])
                @php
                    $hasStayDates = filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null);
                    $bookingQuery = collect($filters)->only(['checkin', 'checkout', 'guests', 'rooms'])->filter()->all();
                    $bookingUrl = $hasStayDates
                        ? route('reservations.create', $residence['apartment']).'?'.http_build_query($bookingQuery)
                        : '#apartment-availability';
                @endphp
                <x-apartment-card
                    :apartment="$residence['apartment']"
                    :quote="$residence['apartment']->stay_quote"
                    :filters="$filters"
                    :link-url="$bookingUrl"
                    :booking-label="$hasStayDates ? 'Reserve now' : 'Check availability'"
                    :show-price="$hasStayDates && data_get($residence['apartment']->stay_quote, 'price_available', false)"
                    :booking-enabled="!$hasStayDates || data_get($residence['apartment']->stay_quote, 'price_available', false)"
                    :price-error="$hasStayDates && !$residence['apartment']->stay_quote ? 'Live prices could not be confirmed. Please try again.' : null"
                />
            @endif
        @endforeach
    </div>
@endif
