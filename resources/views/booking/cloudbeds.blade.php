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

        @include('booking.partials.cloudbeds-theme')
    </head>
    <body class="cloudbeds-booking-page" data-cloudbeds-state="loading">
        <x-site-page-header :currency="$currency" />

        <main class="cloudbeds-booking-main cloudbeds-booking-main--immersive">
            @unless ($hasStaySearch)
                <section class="cloudbeds-booking-intro" aria-labelledby="cloudbeds-booking-title">
                    <div>
                        <p class="eyebrow">Direct booking</p>
                        <h1 id="cloudbeds-booking-title">Reserve your stay.</h1>
                    </div>
                    <p>Choose your dates and residence, then complete your reservation securely within Maison Be.</p>
                </section>
            @endunless

            @if ($hasStaySearch)
                <section class="cloudbeds-results-heading" aria-labelledby="cloudbeds-results-title">
                    <h2 id="cloudbeds-results-title">Select your apartment</h2>
                    <p>Choose the residence that feels right for your stay. Availability and rates reflect your selected dates.</p>
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
                    <p>Availability is temporarily unavailable here. Please try again, or continue to our secure booking page.</p>
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

        <script id="maison-apartment-galleries" type="application/json">@json($apartmentGalleries ?? [])</script>

        <script
            data-cb-immersive-experience-root
            src="{{ asset('js/maisonbe-cloudbeds-theme.js') }}?v=20260926-brand-controls-3"
            defer
        ></script>
    </body>
</html>
