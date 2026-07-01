@props([
    'apartment',
    'quote',
    'filters' => [],
])

@php
    $resolveApartmentImage = function (?string $path) {
        if (! filled($path)) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) $path = substr($path, 7);

        return asset($path);
    };

    $images = $apartment->images->map(function ($image) use ($resolveApartmentImage) {
        return $resolveApartmentImage($image->image);
    })->filter()->values();

    if ($images->isEmpty()) {
        $images->push($resolveApartmentImage($apartment->image) ?: asset('media/maisonbe-hero-source.jpg'));
    }

    $slides = $images->take(10)->values();
    $query = collect($filters)->only(['checkin', 'checkout', 'guests', 'rooms'])->filter()->all();
    $showUrl = route('apartments.show', $apartment).($query ? '?'.http_build_query($query) : '');
    $beds = $apartment->no_of_rooms ?: collect([$apartment->bedroom_1, $apartment->bedroom_2, $apartment->bedroom_3, $apartment->bedroom_4, $apartment->bedroom_5, $apartment->bedroom_6])->filter()->count();
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
    $modalId = 'apartment-card-modal-'.$apartment->id;
@endphp

<article class="residence-card" data-apartment-card>
    <div class="residence-gallery" data-card-gallery>
        @foreach ($slides as $index => $image)
            <button class="residence-gallery-slide {{ $index === 0 ? 'is-active' : '' }}" type="button" style="--slide-image: url('{{ $image }}');" data-card-slide data-card-modal-open aria-controls="{{ $modalId }}" aria-label="View {{ $apartment->name }} photos">
                <img src="{{ $image }}" alt="{{ $apartment->name }} at Maison Be" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
            </button>
        @endforeach
        @if ($slides->count() > 1)
            <button class="residence-gallery-control is-previous" type="button" aria-label="Previous photo" data-card-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
            <button class="residence-gallery-control is-next" type="button" aria-label="Next photo" data-card-next><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg></button>
        @endif
        <div class="residence-gallery-meta">
            <span class="residence-gallery-caption">{{ $apartment->name }}</span>
            <button class="residence-gallery-open" type="button" data-card-modal-open aria-controls="{{ $modalId }}"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9" r="1.25"></circle><path d="m21 15-4.5-4.5L8 19"></path></svg>{{ $images->count() }} View all</button>
        </div>
        @if ($slides->count() > 1)
            <div class="residence-gallery-progress" aria-hidden="true">
                @foreach ($slides as $index => $image)<span class="{{ $index === 0 ? 'is-active' : '' }}" data-card-progress></span>@endforeach
            </div>
        @endif
    </div>
    <div class="residence-card-copy">
        <p>Maison Be Residence</p>
        <h3><a href="{{ $showUrl }}">{{ $apartment->name }}</a></h3>
        <ul class="residence-card-highlights">
            <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>Instant confirmation</li>
            @if ($beds)<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20V9a2 2 0 0 1 2-2h8v13"></path><path d="M13 20V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16"></path><path d="M3 20h18"></path></svg>{{ $beds }} {{ \Illuminate\Support\Str::plural('bedroom', $beds) }}</li>@endif
            @if ($apartment->toilets)<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16"></path><path d="M6 12v3a6 6 0 0 0 12 0v-3"></path><path d="M8 12V7a4 4 0 0 1 8 0v5"></path><path d="M4 20h16"></path></svg>{{ rtrim(rtrim(number_format((float) $apartment->toilets, 1), '0'), '.') }} {{ \Illuminate\Support\Str::plural('bathroom', (float) $apartment->toilets) }}</li>@endif
            @for ($bedroom = 1; $bedroom <= min((int) $beds, 6); $bedroom++)
                <li class="residence-card-bed"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20V9"></path><path d="M3 14h18v6"></path><path d="M7 14V8h5a3 3 0 0 1 3 3v3"></path><path d="M7 11h3"></path></svg><span>Bedroom {{ $bedroom }}</span><strong>King Size Bed</strong></li>
            @endfor
        </ul>
        <span class="residence-card-price">{{ $quote['display_nightly'] }} <small>/ night</small></span>
        @if (filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null))
            <a class="residence-card-book" href="{{ route('reservations.create', $apartment).'?'.http_build_query($query) }}">Book now <span aria-hidden="true">→</span></a>
        @endif
    </div>

    <dialog class="apartment-card-modal" id="{{ $modalId }}" data-card-modal>
        <div class="apartment-card-modal-header"><div><p class="eyebrow">Apartment information</p><h2>{{ $apartment->name }}</h2></div><button type="button" data-card-modal-close aria-label="Close apartment information">×</button></div>
        <img src="{{ $images->first() }}" alt="{{ $apartment->name }} at Maison Be">
        <div class="apartment-card-modal-body">
            <p>{{ $apartment->teaser ?: \Illuminate\Support\Str::limit(strip_tags((string) $apartment->description), 180) }}</p>
            <h3>Check availability for {{ $apartment->name }}</h3>
            <form class="apartment-availability-form" action="{{ route('apartments.availability', $apartment) }}" data-availability-form>
                <x-date-range-picker class="availability-date-range" :checkin="$filters['checkin'] ?? ''" :checkout="$filters['checkout'] ?? ''" required />
                <label>Guests<input type="number" name="guests" min="1" max="{{ $apartment->max_adults ?: 20 }}" value="{{ $filters['guests'] ?? 1 }}"></label>
                <button type="submit">Check availability</button>
            </form>
            <p class="apartment-availability-status" aria-live="polite" data-availability-status></p>
            <a class="apartment-book-now" href="#" hidden data-book-now>Book now <span aria-hidden="true">→</span></a>
            @if ($amenityGroups->isNotEmpty())
                <section class="apartment-modal-amenities">
                    <h3>Apartment amenities</h3>
                    <div class="apartment-modal-amenities-grid">
                        @foreach ($amenityGroups as $groupName => $attributes)
                            <article class="apartment-modal-amenity-group">
                                <h4><span aria-hidden="true">✓</span>{{ $groupName }}</h4>
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
        </div>
    </dialog>
</article>

@once
    <script>
        (() => {
            if (window.apartmentCardHandlersReady) return;
            window.apartmentCardHandlersReady = true;

            const openModal = (modal) => {
                if (!modal) return;
                modal.showModal();
                requestAnimationFrame(() => modal.classList.add('is-open'));
            };

            const closeModal = (modal) => {
                if (!modal || !modal.open || modal.classList.contains('is-closing')) return;
                modal.classList.remove('is-open');
                modal.classList.add('is-closing');

                const finish = () => {
                    modal.classList.remove('is-closing');
                    modal.close();
                };

                const timeout = window.setTimeout(finish, 460);
                modal.addEventListener('transitionend', (transitionEvent) => {
                    if (transitionEvent.target !== modal) return;
                    window.clearTimeout(timeout);
                    finish();
                }, { once: true });
            };

            document.addEventListener('click', (event) => {
                const galleryButton = event.target.closest('[data-card-previous], [data-card-next]');
                if (galleryButton) {
                    event.preventDefault();
                    const gallery = galleryButton.closest('[data-card-gallery]');
                    const slides = [...gallery.querySelectorAll('[data-card-slide]')];
                    const progress = [...gallery.querySelectorAll('[data-card-progress]')];
                    let active = slides.findIndex((slide) => slide.classList.contains('is-active'));
                    active = (active + (galleryButton.matches('[data-card-next]') ? 1 : -1) + slides.length) % slides.length;
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === active));
                    progress.forEach((item, index) => item.classList.toggle('is-active', index === active));
                }

                const open = event.target.closest('[data-card-modal-open]');
                if (open) openModal(document.getElementById(open.getAttribute('aria-controls')));

                const close = event.target.closest('[data-card-modal-close]');
                if (close) closeModal(close.closest('[data-card-modal]'));

                if (event.target.matches('[data-card-modal]')) {
                    const bounds = event.target.getBoundingClientRect();
                    const clickedInside = event.clientX >= bounds.left && event.clientX <= bounds.right && event.clientY >= bounds.top && event.clientY <= bounds.bottom;
                    if (!clickedInside) closeModal(event.target);
                }
            });

            document.addEventListener('cancel', (event) => {
                const modal = event.target.closest('[data-card-modal]');
                if (!modal) return;
                event.preventDefault();
                closeModal(modal);
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-availability-form]');
                if (!form) return;
                if (event.defaultPrevented) return;
                event.preventDefault();
                const status = form.parentElement.querySelector('[data-availability-status]');
                const bookNow = form.parentElement.querySelector('[data-book-now]');
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
            });
        })();
    </script>
@endonce
