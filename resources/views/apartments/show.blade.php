<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $apartment->name }} | Maison Be</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        @php
            $images = $apartment->images->map(fn ($image) => filled($image->image) ? (str_starts_with($image->image, 'http') ? $image->image : asset($image->image)) : null)->filter()->values();
            if ($images->isEmpty()) $images->push(filled($apartment->image) ? asset($apartment->image) : asset('media/maisonbe-hero-source.jpg'));
            $amenityGroups = $apartment->attributes
                ->filter(fn ($attribute) => $attribute->parent && $attribute->type === 'apartment_facility')
                ->sort(fn ($a, $b) => [
                    $a->parent->sort_order,
                    $a->sort_order,
                    $a->name,
                ] <=> [
                    $b->parent->sort_order,
                    $b->sort_order,
                    $b->name,
                ])
                ->groupBy(fn ($attribute) => $attribute->parent->name);
        @endphp
        <header class="results-header"><a class="results-wordmark" href="{{ url('/') }}">Maison Be <small>Residences</small></a><a href="{{ route('apartments.index', $filters) }}" class="results-back">All apartments</a></header>
        <main class="apartment-show-main">
            <p class="eyebrow">Maison Be Residence</p><h1>{{ $apartment->name }}</h1>
            <section class="apartment-show-gallery">
                <img src="{{ $images->first() }}" alt="{{ $apartment->name }} at Maison Be">
                @foreach ($images->skip(1)->take(4) as $image)<img src="{{ $image }}" alt="{{ $apartment->name }} residence detail" loading="lazy">@endforeach
            </section>
            <section class="apartment-show-layout">
                <article><p class="eyebrow">About this residence</p><p class="apartment-description">{{ $apartment->description ?: $apartment->teaser ?: 'A thoughtfully appointed Maison Be residence designed for a quieter, more considered stay.' }}</p><ul class="apartment-facts"><li>Instant confirmation</li><li>{{ $apartment->no_of_rooms ?: '—' }} bedrooms</li><li>{{ $apartment->toilets ?: '—' }} bathrooms</li><li>Up to {{ $apartment->max_adults ?: '—' }} guests</li>@if($apartment->floor)<li>{{ $apartment->floor }} floor</li>@endif</ul></article>
                <aside class="apartment-booking-panel"><p class="eyebrow">Reserve {{ $apartment->name }}</p><strong>{{ $apartment->stay_quote['display_nightly'] }} <small>per night</small></strong><form action="{{ route('apartments.availability', $apartment) }}" class="apartment-availability-form" data-availability-form><x-date-range-picker class="availability-date-range" :checkin="$filters['checkin'] ?? ''" :checkout="$filters['checkout'] ?? ''" required /><label>Guests<input type="number" name="guests" min="1" max="{{ $apartment->max_adults ?: 20 }}" value="{{ $filters['guests'] ?? 1 }}"></label><button type="submit">Check availability</button></form><p class="apartment-availability-status" aria-live="polite" data-availability-status></p><a class="apartment-book-now" href="#" hidden data-book-now>Book now <span aria-hidden="true">→</span></a></aside>
            </section>
            @if ($amenityGroups->isNotEmpty())
                <section class="apartment-amenities">
                    <h2>Apartment amenities</h2>
                    <div class="apartment-amenities-grid">
                        @foreach ($amenityGroups as $groupName => $attributes)
                            <article class="apartment-amenity-group">
                                <h3><span aria-hidden="true">✓</span>{{ $groupName }}</h3>
                                <ul>
                                    @foreach ($attributes as $attribute)
                                        <li>{{ $attribute->name }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
        <x-site-footer />
        <script>
            (() => document.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-availability-form]');
                if (!form || event.defaultPrevented) return;
                event.preventDefault();

                const panel = form.parentElement;
                const status = panel.querySelector('[data-availability-status]');
                const bookNow = panel.querySelector('[data-book-now]');
                const submitButton = form.querySelector('button[type="submit"]');

                status.textContent = 'Checking availability...';
                bookNow.hidden = true;
                bookNow.href = '#';
                form.setAttribute('aria-busy', 'true');
                if (submitButton) submitButton.disabled = true;

                try {
                    const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: new FormData(form) });
                    const result = await response.json();
                    status.textContent = result.message || 'We could not check availability.';
                    if (result.available && result.reserve_url) {
                        bookNow.href = result.reserve_url;
                        bookNow.hidden = false;
                    }
                } catch {
                    status.textContent = 'We could not check availability. Please try again.';
                    bookNow.hidden = true;
                    bookNow.href = '#';
                } finally {
                    form.removeAttribute('aria-busy');
                    if (submitButton) submitButton.disabled = false;
                }
            }))();
        </script>
    </body>
</html>
