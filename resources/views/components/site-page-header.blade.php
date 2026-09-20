@props([
    'currency' => null,
])

@php
    $currency ??= request()->attributes->get('currency', [
        'code' => 'USD',
        'symbol' => '$',
        'rate' => 1,
    ]);
@endphp

<header class="site-page-header" style="--site-page-header-image: url('{{ asset('media/maisonbe-listing-exterior.jpg') }}');">
    <nav class="results-hero-nav" aria-label="Page navigation">
        <button class="menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-menu" data-site-menu-open>
            <span></span><span></span><span></span>
        </button>
        <a class="results-wordmark" href="{{ url('/') }}" aria-label="Maison Be Residences home"><x-brand-logo tone="light" /></a>
        <div class="results-actions">
            <x-currency-selector :currency="$currency" tone="light" />
            <a href="{{ url('/') }}" class="results-back">Home</a>
        </div>
    </nav>
</header>

<aside class="site-menu" data-site-menu aria-hidden="true" hidden>
    <header class="menu-header">
        <button class="menu-close" type="button" aria-label="Close navigation" data-site-menu-close><span></span><span></span></button>
        <a class="menu-wordmark" href="{{ url('/') }}" aria-label="Maison Be Residences home"><x-brand-logo /></a>
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
                <a href="{{ route('login') }}">Login</a>
            </div>
        </nav>
        <div class="menu-image"><img src="{{ asset('media/Exterior/StellarMedia-5.jpg') }}" alt="Maison Be Residences entrance"></div>
    </div>
</aside>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menu = document.querySelector('[data-site-menu]');
            const openButton = document.querySelector('[data-site-menu-open]');
            const closeButton = document.querySelector('[data-site-menu-close]');

            const setMenuState = (open) => {
                if (!menu || !openButton || !closeButton) return;

                if (open) {
                    menu.hidden = false;
                    requestAnimationFrame(() => menu.classList.add('is-open'));
                    menu.setAttribute('aria-hidden', 'false');
                    openButton.setAttribute('aria-expanded', 'true');
                    document.body.classList.add('menu-open');
                    return;
                }

                menu.classList.remove('is-open');
                menu.setAttribute('aria-hidden', 'true');
                openButton.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('menu-open');
                window.setTimeout(() => {
                    if (!menu.classList.contains('is-open')) menu.hidden = true;
                }, 320);
            };

            openButton?.addEventListener('click', () => setMenuState(true));
            closeButton?.addEventListener('click', () => setMenuState(false));
            menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuState(false)));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && menu && !menu.hidden) setMenuState(false);
            });
        });
    </script>
@endonce
