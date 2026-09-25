<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Reserve your stay at Maison Be Residences securely through our booking engine.">
        <x-brand-head />

        <title>Book Your Stay | Maison Be Residences</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">

        @vite(['resources/css/app.css'])

        @php
            $currency = request()->attributes->get('currency', [
                'code' => 'USD',
                'symbol' => '$',
                'rate' => 1,
            ]);
            $cloudbedsPropertyCode = config('cloudbeds.property_code', 'ef9dzW');
            $rawCheckin = request('checkin');
            $rawCheckout = request('checkout');
            $adults = max(1, (int) (request('adults') ?: request('guests', 2)));
            $hasStaySearch = filled($rawCheckin) || filled($rawCheckout);

            $displayCheckin = $rawCheckin;
            $displayCheckout = $rawCheckout;

            try {
                if (filled($rawCheckin)) $displayCheckin = \Illuminate\Support\Carbon::parse($rawCheckin)->format('M j, Y');
                if (filled($rawCheckout)) $displayCheckout = \Illuminate\Support\Carbon::parse($rawCheckout)->format('M j, Y');
            } catch (\Throwable $exception) {
                // Keep the raw values if a malformed date reaches this presentation layer.
            }
        @endphp

        <script>
            (() => {
                const url = new URL(window.location.href);
                let changed = false;

                // Maison Be's existing selector uses `guests`; Cloudbeds expects `adults`.
                if (!url.searchParams.has('adults') && url.searchParams.has('guests')) {
                    url.searchParams.set('adults', url.searchParams.get('guests'));
                    url.searchParams.delete('guests');
                    changed = true;
                }

                // Cloudbeds does not consume Maison Be's current rooms count query key.
                if (url.searchParams.has('rooms')) {
                    url.searchParams.delete('rooms');
                    changed = true;
                }

                if (!url.searchParams.has('currency')) {
                    url.searchParams.set('currency', @json(strtoupper($currency['code'] ?? 'USD')));
                    changed = true;
                }

                if (changed) {
                    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
                }
            })();
        </script>

        <script
            data-entry="immersive"
            src="https://static1.cloudbeds.com/booking-engine/latest/loader.js"
            integrity="sha384-hd98w88pqnDT+lRLwyII/mRmbacols+VWYZF3HKdxnIlTYO+cF38GA1/I/+V8U4a"
            crossorigin="anonymous"
            type="text/javascript"
        ></script>

        <style data-cb-immersive-experience-root>
            :is(#cb-bookingengine, .cb-bookingengine-root) {
                --booking-engine-zIndices-sticky: 850;
                --booking-engine-zIndices-modal: 1400;
                --booking-engine-zIndices-popover: 1500;
                --booking-engine-zIndices-tooltip: 1800;
                color: #0a1738;
                background: #fcf9f1 !important;
                font-family: "Instrument Sans", Arial, sans-serif !important;
                letter-spacing: 0;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(h1, h2, h3, h4, h5, h6),
            .cb-portal :where(h1, h2, h3, h4, h5, h6) {
                color: #06112e !important;
                font-family: "Cormorant Garamond", Georgia, serif !important;
                font-weight: 500 !important;
                letter-spacing: 0 !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(button, input, select, textarea),
            .cb-portal :where(button, input, select, textarea) {
                font-family: "Instrument Sans", Arial, sans-serif !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(button, [role="button"]),
            .cb-portal :where(button, [role="button"]) {
                border-radius: 999px !important;
                transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease, background-color 160ms ease, color 160ms ease !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(button, [role="button"]):hover,
            .cb-portal :where(button, [role="button"]):hover {
                transform: translateY(-1px);
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(input, select, textarea),
            .cb-portal :where(input, select, textarea) {
                border-radius: 12px !important;
                border-color: rgba(6, 17, 46, .18) !important;
                color: #06112e !important;
                background-color: #fff !important;
                box-shadow: none !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) :where(input, select, textarea):focus,
            .cb-portal :where(input, select, textarea):focus {
                border-color: #d9aa4c !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(217, 170, 76, .14) !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) a,
            .cb-portal a {
                color: #8b6a27 !important;
                text-underline-offset: .2em;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) img {
                border-radius: 16px;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-primary,
            .cb-portal .maison-cb-primary {
                border-color: #06112e !important;
                color: #fff !important;
                background: #06112e !important;
                box-shadow: 0 10px 24px rgba(6, 17, 46, .18) !important;
                font-weight: 700 !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-primary:hover,
            .cb-portal .maison-cb-primary:hover {
                border-color: #d9aa4c !important;
                color: #06112e !important;
                background: #d9aa4c !important;
                box-shadow: 0 12px 28px rgba(217, 170, 76, .22) !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-secondary,
            .cb-portal .maison-cb-secondary {
                border-color: rgba(6, 17, 46, .18) !important;
                color: #06112e !important;
                background: #fff !important;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-link,
            .cb-portal .maison-cb-link {
                color: #06112e !important;
                font-weight: 700 !important;
                text-decoration-color: #d9aa4c !important;
            }

            .cb-portal {
                color: #0a1738;
                font-family: "Instrument Sans", Arial, sans-serif !important;
            }
        </style>
    </head>
    <body class="cloudbeds-booking-page" data-cloudbeds-state="loading">
        <x-site-page-header :currency="$currency" />

        <main class="cloudbeds-booking-main cloudbeds-booking-main--immersive">
            @if ($hasStaySearch)
                <section class="cloudbeds-stay-context" aria-label="Your selected stay">
                    <div class="cloudbeds-stay-context-copy">
                        <span>Direct booking</span>
                        <strong>Your Maison Be stay</strong>
                    </div>
                    <dl class="cloudbeds-stay-summary">
                        <div>
                            <dt>Check-in</dt>
                            <dd>{{ $displayCheckin ?: 'Choose date' }}</dd>
                        </div>
                        <div>
                            <dt>Check-out</dt>
                            <dd>{{ $displayCheckout ?: 'Choose date' }}</dd>
                        </div>
                        <div>
                            <dt>Guests</dt>
                            <dd>{{ $adults }} {{ \Illuminate\Support\Str::plural('guest', $adults) }}</dd>
                        </div>
                    </dl>
                    <a class="cloudbeds-stay-edit" href="{{ route('booking.cloudbeds') }}">Start a new search</a>
                </section>
            @else
                <section class="cloudbeds-booking-intro" aria-labelledby="cloudbeds-booking-title">
                    <div>
                        <p class="eyebrow">Direct booking</p>
                        <h1 id="cloudbeds-booking-title">Reserve your stay.</h1>
                    </div>
                    <p>Choose your dates and residence, then complete your reservation securely within Maison Be.</p>
                </section>
            @endif

            <section class="cloudbeds-immersive-stage" data-cloudbeds-stage aria-label="Maison Be secure booking">
                <div class="cloudbeds-stage-loading" data-cloudbeds-loading aria-live="polite">
                    <span class="cloudbeds-stage-mark"><x-brand-logo :show-name="false" /></span>
                    <p>Preparing your Maison Be booking experience</p>
                    <span class="cloudbeds-stage-progress" aria-hidden="true"><i></i></span>
                </div>

                <div class="cloudbeds-stage-error" data-cloudbeds-error hidden>
                    <p class="eyebrow">Secure booking</p>
                    <h2>We could not load availability here.</h2>
                    <p>The embedded booking engine needs an authorized Maison Be domain. You can retry, or continue securely with Cloudbeds.</p>
                    <div>
                        <button type="button" data-cloudbeds-retry>Try again</button>
                        <a href="https://hotels.cloudbeds.com/reservation/{{ $cloudbedsPropertyCode }}">Continue to booking</a>
                    </div>
                </div>

                <div class="cloudbeds-booking-embed" data-cloudbeds-embed>
                    <cb-immersive-experience
                        mode="standard"
                        property-code="{{ $cloudbedsPropertyCode }}"
                        currency="{{ strtoupper($currency['code'] ?? 'USD') }}"
                        lang="en"
                        hide-custom-header="yes"
                        hide-custom-footer="yes"
                        hide-property-info="yes"
                        disable-css-title-reset="yes"
                    ></cb-immersive-experience>

                    <noscript>
                        <div class="cloudbeds-booking-noscript">
                            <p>JavaScript is required to use the embedded booking experience.</p>
                            <a href="https://hotels.cloudbeds.com/reservation/{{ $cloudbedsPropertyCode }}">Continue to secure booking</a>
                        </div>
                    </noscript>
                </div>
            </section>

            <section class="cloudbeds-trust-row" aria-label="Booking benefits">
                <span>Direct booking</span>
                <span>Secure checkout</span>
                <span>Live Cloudbeds availability</span>
                <span>Maison Be support</span>
            </section>
        </main>

        <x-site-footer />

        <script data-cb-immersive-experience-root>
            (() => {
                const body = document.body;
                const stage = document.querySelector('[data-cloudbeds-stage]');
                const loading = document.querySelector('[data-cloudbeds-loading]');
                const error = document.querySelector('[data-cloudbeds-error]');
                const retry = document.querySelector('[data-cloudbeds-retry]');
                const primaryLabels = /^(search|add|continue|book|reserve|confirm|complete|pay|checkout|select|choose)$/i;
                const secondaryLabels = /^(promo code|add code|filter|filters|modify|change|back)$/i;
                const linkLabels = /^(view details|details|view offers|terms|policies)$/i;
                let ready = false;
                let timeoutId = null;

                const root = () => document.querySelector('#cb-bookingengine, .cb-bookingengine-root');

                const normalizeText = (element) => (element?.textContent || '').replace(/\s+/g, ' ').trim();

                const brandInteractiveElements = (scope) => {
                    if (!scope?.querySelectorAll) return;

                    scope.querySelectorAll('button, [role="button"]').forEach((button) => {
                        const label = normalizeText(button);
                        button.classList.remove('maison-cb-primary', 'maison-cb-secondary');

                        if (primaryLabels.test(label)) button.classList.add('maison-cb-primary');
                        else if (secondaryLabels.test(label)) button.classList.add('maison-cb-secondary');
                    });

                    scope.querySelectorAll('a').forEach((link) => {
                        if (linkLabels.test(normalizeText(link))) link.classList.add('maison-cb-link');
                    });
                };

                const setState = (state) => {
                    body.dataset.cloudbedsState = state;
                    stage?.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
                    if (loading) loading.hidden = state !== 'loading';
                    if (error) error.hidden = state !== 'error';
                };

                const evaluate = () => {
                    const bookingRoot = root();
                    if (!bookingRoot) return;

                    brandInteractiveElements(bookingRoot);
                    document.querySelectorAll('.cb-portal').forEach(brandInteractiveElements);

                    const text = normalizeText(bookingRoot).toLowerCase();
                    if (text.includes('oops! something went wrong') || text.includes('this page is currently not loading')) {
                        ready = false;
                        setState('error');
                        return;
                    }

                    const meaningfulContent = bookingRoot.querySelector('button, input, img, [role="button"], a');
                    if (meaningfulContent) {
                        ready = true;
                        window.clearTimeout(timeoutId);
                        setState('ready');
                    }
                };

                const observer = new MutationObserver(() => window.requestAnimationFrame(evaluate));
                observer.observe(document.body, { childList: true, subtree: true, characterData: true });

                if ('ResizeObserver' in window && stage) {
                    const resizeObserver = new ResizeObserver(() => {
                        stage.style.setProperty('--cloudbeds-stage-height', `${stage.scrollHeight}px`);
                    });
                    resizeObserver.observe(stage);
                }

                retry?.addEventListener('click', () => window.location.reload());

                timeoutId = window.setTimeout(() => {
                    if (!ready) setState('error');
                }, 15000);

                document.addEventListener('DOMContentLoaded', evaluate, { once: true });
                window.addEventListener('load', evaluate, { once: true });
            })();
        </script>
    </body>
</html>
