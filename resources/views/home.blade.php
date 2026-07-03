<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Maison Be Residences. Private stays in Lagos, thoughtfully considered.">
        <meta name="theme-color" content="#1c211e">

        <title>Maison Be Residences | Lagos</title>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body>
        <main>
            <section class="hero" aria-labelledby="hero-title">
                <video class="hero-video" autoplay muted loop playsinline poster="{{ asset('media/maisonbe-hero-source.jpg') }}" aria-hidden="true">
                    <source src="https://uploads.pendry.com/redesign/wp-content/uploads/2025/07/14163533/PHR-Hero-Page-2025-1280x640-1.mp4" type="video/mp4">
                </video>
                <div class="hero-overlay"></div>

                <header class="hero-header">
                    <button class="menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-menu" id="menu-toggle">
                        <span></span><span></span><span></span>
                    </button>
                    <a class="hero-wordmark" href="/" aria-label="Maison Be Residences home">
                        Maison Be <small>Residences</small>
                    </a>
                    <a class="reserve-link" href="#residences">Reserve</a>
                </header>

                <div class="hero-title-wrap">
                    <h1 id="hero-title">A calmer<br>Lagos.</h1>
                </div>

                <form class="booking-bar" id="stay-search" action="{{ route('apartments.index') }}" method="get">
                    <x-date-range-picker required />
                    <x-rooms-guests-selector />
                    <button class="availability-button" type="submit" id="availability-button">
                        <span class="availability-label">Check availability</span>
                        <span class="availability-spinner" aria-hidden="true"></span>
                    </button>
                </form>
                <a class="hero-scroll" href="#introduction" aria-label="Scroll to Maison Be introduction"><span aria-hidden="true"></span></a>
            </section>

            <aside class="site-menu" id="site-menu" aria-hidden="true" hidden>
                <header class="menu-header">
                    <button class="menu-close" type="button" aria-label="Close navigation" id="menu-close"><span></span><span></span></button>
                    <a class="menu-wordmark" href="/" aria-label="Maison Be Residences home">Maison Be <small>Residences</small></a>
                    <a class="menu-reserve" href="#residences">Reserve</a>
                </header>
                <div class="menu-content">
                    <nav class="menu-nav" aria-label="Main navigation">
                        <div class="menu-links">
                            <p>Lagos</p>
                            <a href="{{ route('apartments.index') }}">Apartments</a>
                            <a href="{{ route('information.events') }}">Amenities and Events</a>
                            <a href="{{ url('information/about-us') }}">About Us</a>
                            <a href="{{ url('information/about-us') }}">Contact Us</a>
                            <a href="{{ route('login') }}">Login</a>
                        </div>
                    </nav>
                    <div class="menu-image"><img src="{{ asset('media/maisonbe-hero-source.jpg') }}" alt="Maison Be residence interior"></div>
                </div>
            </aside>

            <section class="introduction" id="introduction" aria-labelledby="introduction-title" style="--introduction-room-image: url('{{ asset('media/maisonbe-introduction-room.png') }}');">
                <p class="eyebrow">Maison Be Residences</p>
                <div class="introduction-copy">
                    <h2 id="introduction-title">More than a place to stay, Maison Be is a place to belong.</h2>
                    <p>Built around comfort, trust and understated luxury, our residences offer a warm, composed home away from home for short stays, extended visits and every moment in between.</p>
                    <a class="introduction-cta" href="{{ route('apartments.index') }}">View all apartments <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <section class="residences" id="residences" aria-labelledby="residences-title">
                <div class="residences-heading">
                    <p class="eyebrow">Maison Be Residences</p>
                    <div class="residences-heading-content">
                        <h2 id="residences-title">Find your stay.</h2>
                        <div class="residences-heading-summary">
                            <p>Each residence has its own point of view. Choose the space that feels like yours.</p>
                            <a href="{{ route('apartments.index') }}">View all apartments <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                </div>

                <div class="residence-grid">
                    @foreach ($apartments as $apartment)
                        <x-apartment-card :apartment="$apartment" :quote="$apartment->home_quote" :link-url="route('apartments.index')" />
                    @endforeach
                </div>
            </section>

            @if ($apartments->isNotEmpty())
                <section class="residence-stories" id="amenities" aria-label="The Maison Be experience">
                    @foreach ($apartments->take(2) as $apartment)
                        @php
                            $flavourSlides = $apartment->images->map(function ($image) {
                                $path = $image->image;

                                return filled($path)
                                    ? (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') ? $path : asset($path))
                                    : null;
                            })->filter()->values();

                            if ($flavourSlides->isEmpty()) {
                                $fallback = $apartment->image;
                                $flavourSlides->push(filled($fallback)
                                    ? (str_starts_with($fallback, 'http://') || str_starts_with($fallback, 'https://') ? $fallback : asset($fallback))
                                    : asset('media/maisonbe-introduction-room.png'));
                            }
                        @endphp
                        <article class="residence-story {{ $loop->even ? 'is-reversed' : '' }}" @if ($loop->first) style="--story-fountain-image: url('{{ asset('media/maisonbe-fountain-courtyard.png') }}');" @endif>
                            <div class="residence-story-copy">
                                <p class="eyebrow">{{ $loop->first ? 'The Maison Be way' : 'Made for lingering' }}</p>
                                <h2>{{ $loop->first ? 'A more considered way to stay.' : 'Space to settle into your own rhythm.' }}</h2>
                                <p>{{ $loop->first ? 'From the first arrival to the last unhurried morning, each Maison Be residence is designed around the quiet details that make a stay feel effortless.' : 'Thoughtful interiors, generous rooms and a private sense of calm make every visit feel less like a booking and more like coming home.' }}</p>
                                <a href="{{ route('apartments.index') }}">View all apartments <span aria-hidden="true">→</span></a>
                            </div>
                            <div class="residence-story-slider" data-story-slider>
                                @foreach ($flavourSlides as $index => $slide)
                                    <img class="{{ $index === 0 ? 'is-active' : '' }}" src="{{ $slide }}" alt="{{ $apartment->name }} at Maison Be" loading="lazy" data-story-slide>
                                @endforeach
                                @if ($flavourSlides->count() > 1)
                                    <button class="story-slider-control is-previous" type="button" aria-label="Previous {{ $apartment->name }} photo" data-story-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                                    <button class="story-slider-control is-next" type="button" aria-label="Next {{ $apartment->name }} photo" data-story-next><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg></button>
                                @endif
                                <div class="story-slider-count">{{ $flavourSlides->count() }} photos</div>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif

            <section class="enquire" id="enquire">
                <p class="eyebrow">Stay with Maison Be</p>
                <h2>Make yourself at home.</h2>
                <a href="mailto:reservations@maisonberesidences.com">reservations@maisonberesidences.com <span aria-hidden="true">&#8594;</span></a>
            </section>
        </main>

        @php
            $whatsAppDigits = preg_replace('/\D+/', '', (string) ($settings?->store_phone ?? ''));
            $whatsAppUrl = $whatsAppDigits !== '' ? 'https://wa.me/' . $whatsAppDigits : null;
        @endphp
        <footer class="site-footer">
            <div class="site-footer-links">
                @foreach ($information as $page)
                    <a href="{{ filled($page->custom_link) ? $page->custom_link : route('information.show', $page) }}">{{ $page->title }}</a>
                @endforeach
            </div>
            <p>&copy; {{ now()->year }} {{ $settings?->store_name ?: 'Maison Be Residences' }}. All rights reserved.</p>
            @if ($whatsAppUrl)
                <a class="site-footer-whatsapp" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with Maison Be on WhatsApp">
                    <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3.5a12.3 12.3 0 0 0-10.5 18.7L4 28l6-1.4A12.5 12.5 0 1 0 16 3.5Z"></path><path d="M12.4 10.2c-.4-.9-.8-.9-1.2-.9h-.9c-.3 0-.8.1-1.1.5-.4.4-1.5 1.5-1.5 3.8s1.5 4.5 1.7 4.8c.2.3 3 4.8 7.4 6.5 3.6 1.4 4.4 1.1 5.2 1s2.7-1.1 3.1-2.2.4-2.1.3-2.3c-.1-.2-.3-.3-.7-.5s-2.7-1.3-3.1-1.4c-.4-.2-.7-.2-1 .2-.3.4-1.2 1.4-1.4 1.7-.3.3-.5.3-1 .1-2.8-1.3-4.6-3.4-5.1-4-.3-.4 0-.6.2-.8l.7-.8c.2-.3.3-.5.5-.8.2-.3.1-.6 0-.8l-1.4-3.1Z"></path></svg>
                </a>
            @endif
        </footer>

        <section class="gallery-modal" id="gallery-modal" aria-hidden="true" aria-labelledby="gallery-modal-title" hidden>
            <div class="gallery-modal-backdrop" data-gallery-modal-close></div>
            <div class="gallery-modal-dialog" role="dialog" aria-modal="true">
                <div class="gallery-modal-header">
                    <div>
                        <p class="eyebrow">Apartment gallery</p>
                        <h2 id="gallery-modal-title" data-gallery-modal-title></h2>
                    </div>
                    <button class="gallery-modal-close" type="button" aria-label="Close gallery" data-gallery-modal-close><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg></button>
                </div>
                <div class="gallery-modal-stage">
                    <img src="" alt="" data-gallery-modal-image>
                    <button class="gallery-modal-control is-previous" type="button" aria-label="Previous photo" data-gallery-modal-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                    <button class="gallery-modal-control is-next" type="button" aria-label="Next photo" data-gallery-modal-next><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg></button>
                    <p class="gallery-modal-caption" data-gallery-modal-caption></p>
                    <span class="gallery-modal-count" data-gallery-modal-count></span>
                </div>
                <div class="gallery-modal-details">
                    <section data-gallery-modal-description-section hidden>
                        <p class="eyebrow">About this residence</p>
                        <p class="gallery-modal-description" data-gallery-modal-description></p>
                    </section>
                    <section class="gallery-modal-highlights" data-gallery-modal-highlights-section hidden>
                        <p class="eyebrow">Highlights</p>
                        <ul data-gallery-modal-highlights></ul>
                    </section>
                    <section data-gallery-modal-details-section hidden>
                        <p class="eyebrow">Residence details</p>
                        <dl class="gallery-modal-facts" data-gallery-modal-details></dl>
                    </section>
                </div>
            </div>
        </section>

        <script>
            (() => {
                const staySearch = document.getElementById('stay-search');

                staySearch.addEventListener('submit', (event) => {
                    if (event.defaultPrevented) return;
                    const button = document.getElementById('availability-button');
                    event.preventDefault();
                    button.disabled = true;
                    button.classList.add('is-loading');
                    window.setTimeout(() => staySearch.submit(), 140);
                });

                const revealTargets = [
                    ...document.querySelectorAll('.introduction .eyebrow, .introduction-copy, .residences-heading, .residence-card, .residence-story-copy, .residence-story-slider, .enquire .eyebrow, .enquire h2, .enquire a, .site-footer-links, .site-footer p')
                ];

                revealTargets.forEach((target, index) => {
                    target.classList.add('maison-reveal');
                    target.style.setProperty('--maison-delay', `${Math.min(index % 4, 3) * 90}ms`);
                });

                if ('IntersectionObserver' in window) {
                    const revealObserver = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                revealObserver.unobserve(entry.target);
                            }
                        });
                    }, { threshold: .14, rootMargin: '0px 0px -44px 0px' });

                    revealTargets.forEach((target) => revealObserver.observe(target));
                } else {
                    revealTargets.forEach((target) => target.classList.add('is-visible'));
                }

                const menuToggle = document.getElementById('menu-toggle');
                const menu = document.getElementById('site-menu');
                const menuClose = document.getElementById('menu-close');
                const setMenuState = (open) => {
                    if (open) {
                        menu.hidden = false;
                        requestAnimationFrame(() => menu.classList.add('is-open'));
                        menu.setAttribute('aria-hidden', 'false');
                        menuToggle.setAttribute('aria-expanded', 'true');
                        document.body.classList.add('menu-open');
                        return;
                    }

                    menu.classList.remove('is-open');
                    menu.setAttribute('aria-hidden', 'true');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    document.body.classList.remove('menu-open');
                    window.setTimeout(() => {
                        if (!menu.classList.contains('is-open')) menu.hidden = true;
                    }, 320);
                };

                menuToggle.addEventListener('click', () => setMenuState(true));
                menuClose.addEventListener('click', () => setMenuState(false));
                menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuState(false)));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !menu.hidden) setMenuState(false);
                });

                document.querySelectorAll('[data-residence-gallery]').forEach((gallery) => {
                    const slides = JSON.parse(gallery.querySelector('[data-gallery-slider-slides]').textContent);
                    const imageNodes = gallery.querySelectorAll('[data-gallery-slide]');
                    const progressNodes = gallery.querySelectorAll('[data-gallery-progress]');
                    const caption = gallery.querySelector('[data-gallery-caption]');
                    let activeIndex = 0;

                    const showSlide = (index) => {
                        activeIndex = (index + slides.length) % slides.length;
                        imageNodes.forEach((image, imageIndex) => image.classList.toggle('is-active', imageIndex === activeIndex));
                        progressNodes.forEach((mark, markIndex) => mark.classList.toggle('is-active', markIndex === activeIndex));
                        caption.textContent = slides[activeIndex].caption || imageNodes[activeIndex].alt.split(': ').pop();
                    };

                    gallery.querySelector('[data-gallery-previous]')?.addEventListener('click', () => showSlide(activeIndex - 1));
                    gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => showSlide(activeIndex + 1));
                });

                document.querySelectorAll('[data-story-slider]').forEach((slider) => {
                    const slides = Array.from(slider.querySelectorAll('[data-story-slide]'));
                    let activeIndex = 0;
                    const showSlide = (index) => {
                        activeIndex = (index + slides.length) % slides.length;
                        slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === activeIndex));
                    };
                    slider.querySelector('[data-story-previous]')?.addEventListener('click', () => showSlide(activeIndex - 1));
                    slider.querySelector('[data-story-next]')?.addEventListener('click', () => showSlide(activeIndex + 1));
                });

                const galleryModal = document.getElementById('gallery-modal');
                const modalTitle = galleryModal.querySelector('[data-gallery-modal-title]');
                const modalImage = galleryModal.querySelector('[data-gallery-modal-image]');
                const modalCaption = galleryModal.querySelector('[data-gallery-modal-caption]');
                const modalCount = galleryModal.querySelector('[data-gallery-modal-count]');
                const modalDescriptionSection = galleryModal.querySelector('[data-gallery-modal-description-section]');
                const modalDescription = galleryModal.querySelector('[data-gallery-modal-description]');
                const modalHighlightsSection = galleryModal.querySelector('[data-gallery-modal-highlights-section]');
                const modalHighlights = galleryModal.querySelector('[data-gallery-modal-highlights]');
                const modalDetailsSection = galleryModal.querySelector('[data-gallery-modal-details-section]');
                const modalDetails = galleryModal.querySelector('[data-gallery-modal-details]');
                let modalSlides = [];
                let modalIndex = 0;
                let modalName = '';
                let lastModalTrigger = null;

                const renderModalSlide = (animate = false) => {
                    const slide = modalSlides[modalIndex];
                    const updateSlide = () => {
                        modalImage.src = slide.src;
                        modalImage.alt = `${modalName}${slide.caption ? `: ${slide.caption}` : ''}`;
                        modalCaption.textContent = slide.caption || modalName;
                        modalCount.textContent = `${modalIndex + 1} / ${modalSlides.length}`;
                        const reveal = () => requestAnimationFrame(() => modalImage.classList.remove('is-changing'));
                        modalImage.onload = reveal;
                        window.setTimeout(reveal, 450);
                    };

                    if (animate) {
                        modalImage.classList.add('is-changing');
                        window.setTimeout(updateSlide, 110);
                        return;
                    }

                    updateSlide();
                    modalImage.classList.remove('is-changing');
                };
                const closeGalleryModal = () => {
                    galleryModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('gallery-modal-open');
                    galleryModal.classList.remove('is-open');
                    window.setTimeout(() => {
                        if (!galleryModal.classList.contains('is-open')) {
                            galleryModal.hidden = true;
                            lastModalTrigger?.focus();
                        }
                    }, 220);
                };
                const openGalleryModal = (gallery, trigger) => {
                    modalSlides = JSON.parse(gallery.querySelector('[data-gallery-all-slides]').textContent);
                    const info = JSON.parse(gallery.querySelector('[data-gallery-info]').textContent);
                    modalIndex = Array.from(gallery.querySelectorAll('[data-gallery-slide]')).findIndex((image) => image.classList.contains('is-active'));
                    modalIndex = modalIndex < 0 ? 0 : modalIndex;
                    modalName = gallery.dataset.galleryName;
                    lastModalTrigger = trigger;
                    modalTitle.textContent = modalName;
                    modalDescription.textContent = info.description;
                    modalDescriptionSection.hidden = !info.description;
                    modalHighlights.replaceChildren(...info.highlights.map((highlight) => {
                        const item = document.createElement('li');
                        item.textContent = highlight;
                        return item;
                    }));
                    modalHighlightsSection.hidden = !info.highlights.length;
                    modalDetails.replaceChildren(...info.details.flatMap((detail) => {
                        const label = document.createElement('dt');
                        label.textContent = detail.label;
                        const value = document.createElement('dd');
                        value.textContent = detail.value;
                        return [label, value];
                    }));
                    modalDetailsSection.hidden = !info.details.length;
                    renderModalSlide();
                    galleryModal.hidden = false;
                    galleryModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('gallery-modal-open');
                    requestAnimationFrame(() => galleryModal.classList.add('is-open'));
                    galleryModal.querySelector('.gallery-modal-close').focus();
                };

                document.querySelectorAll('[data-gallery-open]').forEach((button) => {
                    button.addEventListener('click', () => openGalleryModal(button.closest('[data-residence-gallery]'), button));
                });
                document.querySelectorAll('[data-residence-gallery]').forEach((gallery) => {
                    gallery.addEventListener('click', (event) => {
                        if (event.target.closest('button')) return;
                        openGalleryModal(gallery, gallery.querySelector('[data-gallery-open]'));
                    });
                });
                galleryModal.querySelectorAll('[data-gallery-modal-close]').forEach((button) => button.addEventListener('click', closeGalleryModal));
                galleryModal.querySelector('[data-gallery-modal-previous]').addEventListener('click', () => {
                    modalIndex = (modalIndex + modalSlides.length - 1) % modalSlides.length;
                    renderModalSlide(true);
                });
                galleryModal.querySelector('[data-gallery-modal-next]').addEventListener('click', () => {
                    modalIndex = (modalIndex + 1) % modalSlides.length;
                    renderModalSlide(true);
                });
                galleryModal.addEventListener('click', (event) => {
                    if (event.target === galleryModal || event.target.matches('.gallery-modal-backdrop')) closeGalleryModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (galleryModal.hidden) return;
                    if (event.key === 'Escape') closeGalleryModal();
                    if (event.key === 'ArrowLeft') galleryModal.querySelector('[data-gallery-modal-previous]').click();
                    if (event.key === 'ArrowRight') galleryModal.querySelector('[data-gallery-modal-next]').click();
                });
            })();
        </script>
    </body>
</html>
