<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Available Residence | Maison Be</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        <header class="results-hero" style="--results-hero-image: url('{{ asset('media/maisonbe-listing-exterior.jpg') }}');">
            <nav class="results-hero-nav">
                <button class="menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-menu" id="menu-toggle">
                    <span></span><span></span><span></span>
                </button>
                <a class="results-wordmark" href="{{ url('/') }}" aria-label="Maison Be Residences home"><x-brand-logo tone="light" /></a>
                <div class="results-actions">
                    <a href="{{ url('/') }}" class="results-back">Home</a>
                </div>
            </nav>
            <div class="results-hero-copy"><h1>Book your stay</h1><p>Space, comfort and a quieter way to arrive.</p></div>
        </header>

        <aside class="site-menu" id="site-menu" aria-hidden="true" hidden>
            <header class="menu-header">
                <button class="menu-close" type="button" aria-label="Close navigation" id="menu-close"><span></span><span></span></button>
                <a class="menu-wordmark" href="/" aria-label="Maison Be Residences home"><x-brand-logo /></a>
                <a class="menu-reserve" href="{{ route('apartments.index') }}">Reserve</a>
            </header>
            <div class="menu-content">
                <nav class="menu-nav" aria-label="Main navigation">
                    <div class="menu-links">
                        <p>Lagos</p>
                        <a href="{{ route('apartments.index') }}">Apartments</a>
                        <a href="{{ route('information.events') }}">Amenities and Events</a>
                        <a href="{{ url('information/about-us') }}">About Us</a>
                        <a href="{{ route('information.contact') }}">Contact Us</a>
                        @auth
                            <form class="menu-auth-form" method="post" action="{{ route('logout') }}" style="margin:0;">
                                @csrf
                                <button type="submit" style="display:block;margin-top:.55rem;padding:0;border:0;color:var(--soft-ink);background:transparent;font:inherit;font-size:1rem;line-height:inherit;text-align:left;appearance:none;cursor:pointer;">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}">Login</a>
                        @endauth
                    </div>
                </nav>
                @php
                    $menuImageUrl = filled($menuImage ?? null)
                        ? (str_starts_with($menuImage, 'http://') || str_starts_with($menuImage, 'https://') ? $menuImage : asset($menuImage))
                        : asset('media/maisonbe-hero-source.jpg');
                @endphp
                <div class="menu-image"><img src="{{ $menuImageUrl }}" alt="Maison Be apartment interior"></div>
            </div>
        </aside>
        <main class="results-main">
            <h1 class="u-mb-0">Select your apartment.</h1>
            <form class="results-search" id="apartment-availability" method="get" action="{{ route('apartments.index') }}" data-results-search data-initial-load="{{ ($deferResults ?? false) ? 'true' : 'false' }}">
                <input type="hidden" name="search" value="1">
                <input type="hidden" name="currency" value="{{ strtoupper($currency['code'] ?? 'USD') }}">
                <input type="hidden" name="utm_source" value="maisonbe_website">
                <x-date-range-picker class="results-date-range" :checkin="$filters['checkin'] ?? ''" :checkout="$filters['checkout'] ?? ''" required />
                <x-rooms-guests-selector class="results-rooms-guests" :guests="$filters['guests'] ?? 1" :rooms="$filters['rooms'] ?? 2" />
                <button type="submit">Check availability</button>
            </form>
            <section class="results-async" data-results-async aria-live="polite" aria-busy="false">
                <div class="results-loader" @unless($deferResults ?? false) hidden @endunless data-results-loader>
                    <div class="results-grid residence-grid">
                        @for ($i = 0; $i < 6; $i++)
                            <article class="residence-card residence-card-skeleton" aria-hidden="true">
                                <div class="skeleton-gallery"><span></span><span></span></div>
                                <div class="skeleton-copy">
                                    <span class="skeleton-line is-kicker"></span>
                                    <span class="skeleton-line is-title"></span>
                                    <span class="skeleton-line"></span>
                                    <span class="skeleton-line is-short"></span>
                                    <span class="skeleton-divider"></span>
                                    <span class="skeleton-line is-price"></span>
                                    <span class="skeleton-line is-link"></span>
                                </div>
                            </article>
                        @endfor
                    </div>
                </div>
                <div data-results-content @if($deferResults ?? false) hidden @endif>
                    @unless($deferResults ?? false)
                        @include('apartments.partials.results', ['residences' => $residences, 'filters' => $filters, 'currency' => $currency, 'cloudbedsError' => $cloudbedsError])
                    @endunless
                </div>
            </section>
        </main>
        <x-site-footer />
        @include('components.apartment-card-handlers')
        <script>
            (() => {
                const menu = document.getElementById('site-menu');
                const menuToggle = document.getElementById('menu-toggle');
                const menuClose = document.getElementById('menu-close');
                const setMenuState = (open) => {
                    if (!menu || !menuToggle || !menuClose) return;

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

                menuToggle?.addEventListener('click', () => setMenuState(true));
                menuClose?.addEventListener('click', () => setMenuState(false));
                menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuState(false)));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && menu && !menu.hidden) setMenuState(false);
                });

                const form = document.querySelector('[data-results-search]');
                if (!form) return;

                // Apartment cards are browse-first. Their availability CTA brings the
                // guest back to this date selector instead of starting a date-less booking.
                document.addEventListener('click', (event) => {
                    const availabilityLink = event.target.closest('a[href="#apartment-availability"]');
                    if (!availabilityLink) return;

                    event.preventDefault();
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    window.setTimeout(() => {
                        const checkin = form.querySelector('[data-checkin-input]');
                        const checkout = form.querySelector('[data-checkout-input]');
                        const field = checkin?.value && !checkout?.value ? 'checkout' : 'checkin';
                        const trigger = form.querySelector(`[data-date-trigger][data-date-field="${field}"]`);
                        const picker = form.querySelector('[data-date-picker]');

                        if (picker?.hidden) trigger?.click();
                    }, 260);
                });

                const asyncRegion = document.querySelector('[data-results-async]');
                const loader = document.querySelector('[data-results-loader]');
                const content = document.querySelector('[data-results-content]');
                let activeRequest = null;

                const setLoading = (loading) => {
                    asyncRegion?.setAttribute('aria-busy', loading ? 'true' : 'false');
                    if (loader) loader.hidden = !loading;
                    if (content) content.hidden = loading;
                    const submit = form.querySelector('button[type="submit"]');
                    if (submit) submit.disabled = loading;
                };

                const showAjaxError = (message) => {
                    if (!content) return;
                    content.replaceChildren();
                    const notice = document.createElement('p');
                    notice.className = 'results-notice results-notice-error';
                    notice.textContent = message;
                    content.appendChild(notice);
                    content.hidden = false;
                };

                const loadResults = async ({ updateUrl = true } = {}) => {
                    const checkin = form.querySelector('[data-checkin-input]');
                    const checkout = form.querySelector('[data-checkout-input]');

                    if (!checkin?.value || !checkout?.value) {
                        const field = checkin?.value ? 'checkout' : 'checkin';
                        form.querySelector(`[data-date-trigger][data-date-field="${field}"]`)?.click();
                        return;
                    }

                    if (!form.reportValidity()) return;

                    if (activeRequest) activeRequest.abort();
                    const requestController = new AbortController();
                    activeRequest = requestController;

                    const params = new URLSearchParams(new FormData(form));
                    params.set('search', '1');
                    const url = `${form.action}?${params.toString()}`;

                    setLoading(true);

                    try {
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'text/html',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: requestController.signal,
                        });

                        if (!response.ok) {
                            let message = 'We could not load live availability. Please try again.';
                            if (response.status === 422) {
                                try {
                                    const payload = await response.json();
                                    const firstError = Object.values(payload.errors ?? {}).flat()[0];
                                    if (firstError) message = firstError;
                                } catch (_) {}
                            }
                            throw new Error(message);
                        }

                        const html = await response.text();
                        if (content) {
                            content.innerHTML = html;
                            content.hidden = false;
                        }

                        if (updateUrl) {
                            window.history.replaceState({ maisonbeAvailability: true }, '', url);
                        }
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        showAjaxError(error.message || 'We could not load live availability. Please try again.');
                    } finally {
                        if (activeRequest === requestController) {
                            activeRequest = null;
                            setLoading(false);
                        }
                    }
                };

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    loadResults();
                });

                if (form.dataset.initialLoad === 'true') {
                    loadResults({ updateUrl: false });
                }
            })();
        </script>
    </body>
</html>
