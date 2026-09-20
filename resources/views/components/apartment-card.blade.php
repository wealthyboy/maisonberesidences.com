@props([
    'apartment',
    'quote',
    'filters' => [],
    'linkUrl' => null,
    'bookingEnabled' => true,
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
        return [
            'url' => $resolveApartmentImage($image->image),
            'caption' => trim((string) $image->caption),
        ];
    })->filter(fn ($image) => filled($image['url']))->values();

    if ($images->isEmpty()) {
        $images->push([
            'url' => $resolveApartmentImage($apartment->image) ?: asset('media/maisonbe-hero-source.jpg'),
            'caption' => '',
        ]);
    }

    $cardSlides = $images->take(10)->values();
    $modalSlides = $images->values();
    $highlightSource = $apartment->teaser ?: strip_tags((string) $apartment->description);
    $knownHighlights = ['Air conditioning', 'Flat-screen TV', 'Pillowtop bed', 'Premium bedding', 'Blackout drapes/curtains', 'Private Family Lounge'];
    $highlights = collect($knownHighlights)
        ->filter(fn ($highlight) => str_contains(strtolower($highlightSource), strtolower($highlight)))
        ->values();

    if ($highlights->isEmpty() && filled($highlightSource)) {
        $highlights = collect(preg_split('/[,;\\n]+/', $highlightSource))
            ->map(fn ($highlight) => trim($highlight))
            ->filter()
            ->take(6)
            ->values();
    }

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
    $cardAmenities = $amenityGroups->flatten(1);
    $parkingAmenity = $cardAmenities->first(fn ($attribute) => strtolower(trim($attribute->name)) === 'parking included');
    $wifiAmenity = $cardAmenities->first(fn ($attribute) => strtolower(trim($attribute->name)) === 'free wifi');
    $featurePriority = collect([
        'Climate-controlled air conditioning',
        'Blackout drapes/curtains',
        'Flat-screen TV',
        'Cinema',
        'Hot tub/Jacuzzi',
        'Elevator',
    ])->flip();
    $modalFeatureAmenities = $cardAmenities
        ->reject(fn ($attribute) => in_array(strtolower(trim($attribute->name)), [
            'parking included',
            'free wifi',
            'breakfast buffet',
            'reserve now, pay later',
        ], true))
        ->sortBy(fn ($attribute) => $featurePriority->get($attribute->name, 1000 + $attribute->sort_order))
        ->take(6);
    $groupIcons = [
        'Bathroom' => 'bathroom',
        'Bedroom' => 'bed',
        'Comfort & Essentials' => 'bed',
        'Outdoors' => 'pool',
        'Living Area' => 'sofa',
        'Entertainment' => 'tv',
        'Environment & Sustainability' => 'air-conditioning',
        'Internet' => 'wifi',
        'Wellness' => 'hot-tub',
        'Kitchen & Dining' => 'kitchen',
        'Food and drink' => 'room-service',
        'More' => 'check',
        'Safety & Security' => 'check',
        'Accessibility' => 'elevator',
    ];
    $bedSummary = collect([
        $apartment->bedroom_1,
        $apartment->bedroom_2,
        $apartment->bedroom_3,
        $apartment->bedroom_4,
        $apartment->bedroom_5,
        $apartment->bedroom_6,
    ])->filter()->countBy()->map(function ($count, $bed) {
        return $count > 1 ? $count.' '.\Illuminate\Support\Str::plural($bed, $count) : $bed;
    })->implode(', ');
    $size = trim((string) ($apartment->size_sq_ft ?: $apartment->property?->size ?? ''));
    $displaySize = $size !== '' ? (is_numeric($size) ? number_format((float) $size).' sq ft' : $size) : null;
    $bathroomValue = $apartment->toilets
        ?: $apartment->property?->bathrooms
        ?: $apartment->property?->toilets
        ?: 3.5;
    $bathrooms = rtrim(rtrim(number_format((float) $bathroomValue, 1), '0'), '.');
    $refundability = 'Refundable 14 days or more before check-in';
    $modalId = 'apartment-card-modal-'.$apartment->id;
    $hasStayDates = filled($filters['checkin'] ?? null) && filled($filters['checkout'] ?? null);
    $bookUrl = $hasStayDates
        ? route('reservations.create', $apartment).'?'.http_build_query($query)
        : null;
@endphp

<article class="residence-card" data-apartment-card>
    <div class="residence-gallery" data-card-gallery>
        @foreach ($cardSlides as $index => $image)
            <button class="residence-gallery-slide {{ $index === 0 ? 'is-active' : '' }}" type="button" style="--slide-image: url('{{ $image['url'] }}');" data-card-slide data-caption="{{ $image['caption'] }}" data-card-modal-open aria-controls="{{ $modalId }}" aria-label="View {{ $apartment->name }} photos">
                <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?: $apartment->name.' at Maison Be' }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
            </button>
        @endforeach
        @if ($cardSlides->count() > 1)
            <button class="residence-gallery-control is-previous" type="button" aria-label="Previous photo" data-card-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
            <button class="residence-gallery-control is-next" type="button" aria-label="Next photo" data-card-next><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg></button>
        @endif
        <div class="residence-gallery-meta">
            <span class="residence-gallery-caption" data-card-caption @if(blank($cardSlides->first()['caption'] ?? null)) hidden @endif>{{ $cardSlides->first()['caption'] ?? '' }}</span>
            <button class="residence-gallery-open" type="button" data-card-modal-open aria-controls="{{ $modalId }}" aria-label="View all {{ $images->count() }} photos"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9" r="1.25"></circle><path d="m21 15-4.5-4.5L8 19"></path></svg>{{ $images->count() }}</button>
        </div>
        @if ($cardSlides->count() > 1)
            <div class="residence-gallery-progress" aria-hidden="true">
                @foreach ($cardSlides as $index => $image)<span class="{{ $index === 0 ? 'is-active' : '' }}" data-card-progress></span>@endforeach
            </div>
        @endif
    </div>
    <div class="residence-card-copy">
        <p>Maison Be Residences</p>
        <h3><a href="{{ $showUrl }}">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($apartment->name)) }}</a></h3>
        <ul class="residence-card-highlights">
            @if ($parkingAmenity)<li class="is-parking"><x-amenity-icon name="parking" />{{ $parkingAmenity->name }}</li>@endif
            @if ($displaySize)<li><x-amenity-icon name="area" />{{ $displaySize }}</li>@endif
            @if ($beds)<li><x-amenity-icon name="bedrooms" />{{ $beds }} {{ \Illuminate\Support\Str::plural('Bedroom', $beds) }}</li>@endif
            @if ($bathrooms)<li><x-amenity-icon name="bathroom" />{{ $bathrooms }} {{ \Illuminate\Support\Str::plural('Bathroom', (float) $bathrooms) }}</li>@endif
            @if ($apartment->max_adults)<li><x-amenity-icon name="guests" />Sleeps {{ $apartment->max_adults }}</li>@endif
            @if ($bedSummary)<li><x-amenity-icon name="bed" />{{ \Illuminate\Support\Str::title($bedSummary) }}</li>@endif
            @if ($wifiAmenity || filled($apartment->wifi_ssid))<li><x-amenity-icon name="wifi" />Free WiFi</li>@endif
        </ul>
        <div class="residence-card-details">
            <span>{{ $refundability }} <x-amenity-icon name="info" /></span>
            <button type="button" data-card-modal-open aria-controls="{{ $modalId }}">More details <x-amenity-icon name="chevron-right" /></button>
        </div>
        <div class="residence-card-footer">
            <div class="residence-card-rate">
                <span class="residence-card-price">
                    <strong>{{ $quote['display_nightly'] }}</strong>
                    <small>per night</small>
                </span>
            </div>
            @if ($bookingEnabled && $bookUrl)
                <a class="residence-card-book" href="{{ $bookUrl }}">Book now</a>
            @endif
        </div>
    </div>

    <dialog class="apartment-card-modal" id="{{ $modalId }}" data-card-modal>
        <div class="apartment-card-modal-header">
            <h2>Room information</h2>
            <button type="button" data-card-modal-close aria-label="Close room information">×</button>
        </div>
        <div class="apartment-card-modal-slider" data-modal-slider>
            @foreach ($modalSlides as $index => $image)
                <figure class="apartment-card-modal-slide {{ $index === 0 ? 'is-active' : '' }}" data-modal-slide>
                    <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?: $apartment->name.' at Maison Be' }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
                </figure>
            @endforeach
            @if ($modalSlides->count() > 1)
                <button class="apartment-card-modal-control is-previous" type="button" aria-label="Previous apartment photo" data-modal-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                <button class="apartment-card-modal-control is-next" type="button" aria-label="Next apartment photo" data-modal-next><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg></button>
                <span class="apartment-card-modal-count" data-modal-count>1 / {{ $modalSlides->count() }}</span>
                <div class="apartment-card-modal-progress" aria-hidden="true">
                    @foreach ($modalSlides as $index => $image)<span class="{{ $index === 0 ? 'is-active' : '' }}" data-modal-progress></span>@endforeach
                </div>
            @endif
        </div>
        <div class="apartment-card-modal-body">
            <h3 class="apartment-modal-room-name">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($apartment->name)) }}</h3>

            @if ($modalFeatureAmenities->isNotEmpty())
                <ul class="apartment-modal-feature-grid">
                    @foreach ($modalFeatureAmenities as $attribute)
                        <li>
                            <x-amenity-icon :name="$attribute->icon ?: 'check'" />
                            <span>{{ $attribute->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <ul class="apartment-modal-facts">
                @if ($parkingAmenity)<li class="is-parking"><x-amenity-icon name="parking" />{{ $parkingAmenity->name }}</li>@endif
                @if ($displaySize)<li><x-amenity-icon name="area" />{{ $displaySize }}</li>@endif
                @if ($beds)<li><x-amenity-icon name="bedrooms" />{{ $beds }} {{ \Illuminate\Support\Str::plural('Bedroom', $beds) }}</li>@endif
                @if ($bathrooms)<li><x-amenity-icon name="bathroom" />{{ $bathrooms }} {{ \Illuminate\Support\Str::plural('Bathroom', (float) $bathrooms) }}</li>@endif
                @if ($apartment->max_adults)<li><x-amenity-icon name="guests" />Sleeps {{ $apartment->max_adults }}</li>@endif
                @if ($bedSummary)<li><x-amenity-icon name="bed" />{{ \Illuminate\Support\Str::title($bedSummary) }}</li>@endif
                @if ($wifiAmenity || filled($apartment->wifi_ssid))<li><x-amenity-icon name="wifi" />Free WiFi</li>@endif
            </ul>

            @if ($amenityGroups->isNotEmpty())
                <section class="apartment-modal-amenities">
                    <h3>Room amenities</h3>
                    <div class="apartment-modal-amenities-grid">
                        @foreach ($amenityGroups as $groupName => $attributes)
                            <article class="apartment-modal-amenity-group">
                                <h4><x-amenity-icon :name="$groupIcons[$groupName] ?? ($attributes->first()?->icon ?: 'check')" />{{ $groupName }}</h4>
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
                const modalSliderButton = event.target.closest('[data-modal-previous], [data-modal-next]');
                if (modalSliderButton) {
                    event.preventDefault();
                    const slider = modalSliderButton.closest('[data-modal-slider]');
                    const slides = [...slider.querySelectorAll('[data-modal-slide]')];
                    const progress = [...slider.querySelectorAll('[data-modal-progress]')];
                    const count = slider.querySelector('[data-modal-count]');
                    let active = slides.findIndex((slide) => slide.classList.contains('is-active'));
                    active = (active + (modalSliderButton.matches('[data-modal-next]') ? 1 : -1) + slides.length) % slides.length;
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === active));
                    progress.forEach((item, index) => item.classList.toggle('is-active', index === active));
                    if (count) count.textContent = `${active + 1} / ${slides.length}`;
                }

                const galleryButton = event.target.closest('[data-card-previous], [data-card-next]');
                if (galleryButton) {
                    event.preventDefault();
                    const gallery = galleryButton.closest('[data-card-gallery]');
                    const slides = [...gallery.querySelectorAll('[data-card-slide]')];
                    const progress = [...gallery.querySelectorAll('[data-card-progress]')];
                    const caption = gallery.querySelector('[data-card-caption]');
                    let active = slides.findIndex((slide) => slide.classList.contains('is-active'));
                    active = (active + (galleryButton.matches('[data-card-next]') ? 1 : -1) + slides.length) % slides.length;
                    slides.forEach((slide, index) => slide.classList.toggle('is-active', index === active));
                    progress.forEach((item, index) => item.classList.toggle('is-active', index === active));
                    if (caption) {
                        caption.textContent = slides[active].dataset.caption ?? '';
                        caption.hidden = caption.textContent.trim() === '';
                    }
                }

                const open = event.target.closest('[data-card-modal-open]');
                if (open) {
                    const modal = document.getElementById(open.getAttribute('aria-controls'));
                    const card = open.closest('[data-apartment-card]');
                    const cardSlides = [...card.querySelectorAll('[data-card-slide]')];
                    const activeIndex = Math.max(0, cardSlides.findIndex((slide) => slide.classList.contains('is-active')));
                    const modalSlides = [...modal.querySelectorAll('[data-modal-slide]')];
                    const modalProgress = [...modal.querySelectorAll('[data-modal-progress]')];
                    const modalCount = modal.querySelector('[data-modal-count]');
                    modalSlides.forEach((slide, index) => slide.classList.toggle('is-active', index === activeIndex));
                    modalProgress.forEach((item, index) => item.classList.toggle('is-active', index === activeIndex));
                    if (modalCount) modalCount.textContent = `${activeIndex + 1} / ${modalSlides.length}`;
                    openModal(modal);
                }

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
                if (submitButton?.dataset.available === 'true' && submitButton.dataset.reserveUrl) {
                    window.location.href = submitButton.dataset.reserveUrl;
                    return;
                }

                status.textContent = '';
                status.classList.remove('is-success', 'is-error');
                bookNow.hidden = true;
                bookNow.href = '#';
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
                    status.textContent = result.message || 'We could not check availability.';
                    if (result.available && result.reserve_url) {
                        status.classList.add('is-success');
                        bookNow.href = result.reserve_url;
                        if (submitButton) {
                            submitButton.textContent = 'Book now →';
                            submitButton.dataset.available = 'true';
                            submitButton.dataset.reserveUrl = result.reserve_url;
                        }
                    } else {
                        status.classList.add('is-error');
                        if (submitButton) submitButton.textContent = 'Check availability';
                    }
                } catch {
                    status.textContent = 'We could not check availability. Please try again.';
                    status.classList.add('is-error');
                    bookNow.hidden = true;
                    bookNow.href = '#';
                    if (submitButton) submitButton.textContent = 'Check availability';
                } finally {
                    form.removeAttribute('aria-busy');
                    if (submitButton) submitButton.disabled = false;
                }
            });
        })();
    </script>
@endonce
