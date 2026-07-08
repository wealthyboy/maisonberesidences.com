<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Available Residence | Maison Be</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        <header class="results-hero" style="--results-hero-image: url('{{ asset('media/maisonbe-hero-source.jpg') }}');">
            <nav class="results-hero-nav">
                <button class="menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-menu" id="menu-toggle">
                    <span></span><span></span><span></span>
                </button>
                <a class="results-wordmark" href="{{ url('/') }}">Maison Be <small>Residence</small></a>
                <div class="results-actions">
                    <a href="{{ request()->fullUrlWithQuery(['currency' => $currency['code'] === 'NGN' ? 'USD' : 'NGN']) }}" class="currency-switch">{{ $currency['code'] === 'NGN' ? 'View USD' : 'View NGN' }}</a>
                    <a href="{{ url('/') }}" class="results-back">Home</a>
                </div>
            </nav>
            <div class="results-hero-copy"><h1>Book your stay</h1><p>Space, comfort and a quieter way to arrive.</p></div>
        </header>

        <aside class="site-menu" id="site-menu" aria-hidden="true" hidden>
            <header class="menu-header">
                <button class="menu-close" type="button" aria-label="Close navigation" id="menu-close"><span></span><span></span></button>
                <a class="menu-wordmark" href="/" aria-label="Maison Be Residence home">Maison Be <small>Residence</small></a>
                <a class="menu-reserve" href="{{ route('apartments.index') }}">Reserve</a>
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
        <main class="results-main">
            <h1 class="u-mb-0">Select your apartment.</h1>
            <form class="results-search" method="get" action="{{ route('apartments.index') }}" data-results-search>
                <x-date-range-picker class="results-date-range" :checkin="$filters['checkin'] ?? ''" :checkout="$filters['checkout'] ?? ''" required />
                <x-rooms-guests-selector class="results-rooms-guests" :guests="$filters['guests'] ?? 1" :rooms="$filters['rooms'] ?? 1" />
                <button type="submit">Check availability</button>
            </form>
            <section class="results-async" data-results-async aria-live="polite" aria-busy="false">
                <div class="results-loader" hidden data-results-loader>
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
                <div data-results-content>
                    @include('apartments.partials.results', ['apartments' => $apartments, 'filters' => $filters, 'currency' => $currency])
                </div>
            </section>
        </main>
        <x-site-footer />
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
                const region = document.querySelector('[data-results-async]');
                const content = document.querySelector('[data-results-content]');
                const loader = document.querySelector('[data-results-loader]');
                if (!form || !region || !content || !loader) return;

                let activeRequest = null;

                const buildUrl = () => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('page');
                    new FormData(form).forEach((value, key) => {
                        if (value === '') {
                            url.searchParams.delete(key);
                            return;
                        }
                        url.searchParams.set(key, value);
                    });
                    return url;
                };

                const setLoading = (loading) => {
                    region.setAttribute('aria-busy', String(loading));
                    region.classList.toggle('is-loading', loading);
                    loader.hidden = !loading;
                    form.querySelector('button[type="submit"]').disabled = loading;
                };

                const runInlineScripts = (root) => {
                    root.querySelectorAll('script').forEach((script) => {
                        const copy = document.createElement('script');
                        [...script.attributes].forEach((attribute) => copy.setAttribute(attribute.name, attribute.value));
                        copy.textContent = script.textContent;
                        document.body.appendChild(copy);
                        copy.remove();
                    });
                };

                const loadResults = async (url, pushState = true) => {
                    activeRequest?.abort();
                    activeRequest = new AbortController();
                    setLoading(true);

                    try {
                        const response = await fetch(url, {
                            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: activeRequest.signal,
                        });
                        if (!response.ok) throw new Error('Search request failed');
                        content.innerHTML = await response.text();
                        runInlineScripts(content);
                        window.initDateRangePickers?.();
                        window.initRoomsGuests?.();
                        if (pushState) window.history.pushState({}, '', url);
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            content.innerHTML = '<p class="results-empty">We could not complete that search. Please try again.</p>';
                        }
                    } finally {
                        setLoading(false);
                    }
                };

                form.addEventListener('submit', (event) => {
                    if (event.defaultPrevented) return;
                    const checkin = form.querySelector('[data-checkin-input]');
                    const checkout = form.querySelector('[data-checkout-input]');

                    if (!checkin?.value || !checkout?.value) {
                        event.preventDefault();
                        const field = checkin?.value ? 'checkout' : 'checkin';
                        form.querySelector(`[data-date-trigger][data-date-field="${field}"]`)?.click();
                        return;
                    }

                    event.preventDefault();
                    loadResults(buildUrl());
                });

                content.addEventListener('click', (event) => {
                    const link = event.target.closest('.results-pagination a');
                    if (!link) return;
                    event.preventDefault();
                    loadResults(new URL(link.href));
                    region.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

                window.addEventListener('popstate', () => loadResults(new URL(window.location.href), false));
            })();
        </script>
    </body>
</html>
