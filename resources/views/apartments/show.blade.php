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
            $highlightSource = trim((string) ($apartment->description ?: $apartment->teaser));
            $knownHighlights = ['Air conditioning', 'Flat-screen TV', 'Pillowtop bed', 'Premium bedding', 'Blackout drapes/curtains', 'Private Family Lounge'];
            $highlights = collect($knownHighlights)->filter(fn ($highlight) => str_contains(strtolower($highlightSource), strtolower($highlight)))->values();
            if ($highlights->isEmpty() && filled($highlightSource)) {
                $highlights = collect(preg_split('/[,;\\n]+/', $highlightSource))->map(fn ($highlight) => trim($highlight))->filter()->take(6)->values();
            }
        @endphp
        <header class="results-header"><a class="results-wordmark" href="{{ url('/') }}">Maison Be <small>Residences</small></a><a href="{{ route('apartments.index', $filters) }}" class="results-back">All apartments</a></header>
        <main class="apartment-show-main">
            <header class="apartment-show-heading">
                <div>
                    <p class="eyebrow">Maison Be Residence</p>
                    <h1>{{ $apartment->name }}</h1>
                </div>
            </header>
            <section class="apartment-show-gallery">
                <figure>
                    <img src="{{ $images->first() }}" alt="{{ $apartment->name }} at Maison Be">
                </figure>
                @foreach ($images->skip(1)->take(4) as $image)
                    <figure @class(['apartment-gallery-more' => $loop->last])>
                        <img src="{{ $image }}" alt="{{ $apartment->name }} residence detail" loading="lazy">
                        @if ($loop->last)
                            <span><strong>+{{ $images->count() }}</strong>View gallery</span>
                        @endif
                    </figure>
                @endforeach
            </section>
            <section class="apartment-show-layout">
                <article class="apartment-story-card">
                    <p class="eyebrow">About this residence</p>
                    @if ($highlights->isNotEmpty())
                        <div class="apartment-highlight-panel">
                            <span class="apartment-highlight-kicker">Highlights</span>
                            <ul>
                                @foreach ($highlights as $highlight)
                                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l1.65 5.1H19l-4.35 3.12 1.67 5.08L12 13.15 7.68 16.3l1.67-5.08L5 8.1h5.35L12 3Z"></path></svg>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="apartment-description">A thoughtfully appointed Maison Be residence designed for a quieter, more considered stay.</p>
                    @endif
                    <ul class="apartment-facts">
                        <li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7 9 18l-5-5"></path></svg>Confirmation</span><strong>Instant</strong></li>
                        <li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h7v18M4 11h9M9 7h.01M9 15h.01M13 9h5a2 2 0 0 1 2 2v10M16 14h.01M16 18h.01"></path></svg>Bedrooms</span><strong>{{ $apartment->no_of_rooms ?: '—' }}</strong></li>
                        <li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 11V7a5 5 0 0 1 10 0M5 11h14v4a7 7 0 0 1-14 0v-4ZM8 21h8"></path></svg>Bathrooms</span><strong>{{ $apartment->toilets ?: '—' }}</strong></li>
                        <li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0M3 21a7 7 0 0 1 18 0M17 7a3 3 0 0 1 3 3M4 10a3 3 0 0 1 3-3"></path></svg>Guests</span><strong>{{ $apartment->max_adults ?: '—' }}</strong></li>
                        @if($apartment->floor)<li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V9l8-6 8 6v12M9 21v-8h6v8M4 13h5M15 13h5"></path></svg>Floor</span><strong>{{ $apartment->floor }}</strong></li>@endif
                    </ul>
                </article>
                <aside class="apartment-booking-panel">
                    <p class="eyebrow">Reserve {{ $apartment->name }}</p>
                    <strong>{{ $apartment->stay_quote['display_nightly'] }} <small>per night</small></strong>
                    <form action="{{ route('apartments.availability', $apartment) }}" class="apartment-availability-form" data-availability-form>
                        <x-date-range-picker class="availability-date-range" :checkin="$filters['checkin'] ?? ''" :checkout="$filters['checkout'] ?? ''" required />
                        <label>Guests<input type="number" name="guests" min="1" max="{{ $apartment->max_adults ?: 20 }}" value="{{ $filters['guests'] ?? 1 }}"></label>
                        <button type="submit" data-availability-action>Check availability</button>
                    </form>
                    <p class="apartment-availability-status" aria-live="polite" data-availability-status></p>
                </aside>
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
                const submitButton = form.querySelector('button[type="submit"]');
                const actionUrl = submitButton?.dataset.reserveUrl;

                if (submitButton?.dataset.available === 'true' && actionUrl) {
                    window.location.href = actionUrl;
                    return;
                }

                status.textContent = '';
                status.classList.remove('is-error', 'is-success');
                form.setAttribute('aria-busy', 'true');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Checking availability...';
                    submitButton.dataset.available = 'false';
                    submitButton.dataset.reserveUrl = '';
                }

                try {
                    const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: new FormData(form) });
                    const result = await response.json();
                    if (result.available && result.reserve_url) {
                        status.textContent = result.message || 'This apartment is available for your chosen stay.';
                        status.classList.add('is-success');
                        if (submitButton) {
                            submitButton.textContent = 'Book now →';
                            submitButton.dataset.available = 'true';
                            submitButton.dataset.reserveUrl = result.reserve_url;
                        }
                    } else {
                        status.textContent = result.message || 'Apartment not available for your selected date.';
                        status.classList.add('is-error');
                        if (submitButton) submitButton.textContent = 'Check availability';
                    }
                } catch {
                    status.textContent = 'We could not check availability. Please try again.';
                    status.classList.add('is-error');
                    if (submitButton) submitButton.textContent = 'Check availability';
                } finally {
                    form.removeAttribute('aria-busy');
                    if (submitButton) submitButton.disabled = false;
                }
            }))();
        </script>
    </body>
</html>
