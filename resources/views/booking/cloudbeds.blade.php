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

        {{--
            Cloudbeds reads supported search parameters from the page URL when
            the Immersive Experience starts. Maison Be currently uses `guests`
            in a few frontend forms, while Cloudbeds expects `adults`.
            Normalize that value before loading Cloudbeds so existing Maison Be
            links can be connected to this page later without rewriting the
            booking engine itself.
        --}}
        <script>
            (() => {
                const url = new URL(window.location.href);
                let changed = false;

                if (!url.searchParams.has('adults') && url.searchParams.has('guests')) {
                    url.searchParams.set('adults', url.searchParams.get('guests'));
                    url.searchParams.delete('guests');
                    changed = true;
                }

                // Cloudbeds does not use Maison Be's current `rooms` query key.
                if (url.searchParams.has('rooms')) {
                    url.searchParams.delete('rooms');
                    changed = true;
                }

                if (changed) {
                    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
                }
            })();
        </script>

        {{--
            Current Cloudbeds Immersive Experience loader. Maison Be does not
            currently run a Consent Management Platform, so the script is
            executable JavaScript rather than a CMP-blocked text/plain tag.
        --}}
        <script
            data-entry="immersive"
            src="https://static1.cloudbeds.com/booking-engine/latest/loader.js"
            integrity="sha384-hd98w88pqnDT+lRLwyII/mRmbacols+VWYZF3HKdxnIlTYO+cF38GA1/I/+V8U4a"
            crossorigin="anonymous"
            type="text/javascript"
        ></script>

        <style data-cb-immersive-experience-root>
            :is(#cb-bookingengine, .cb-bookingengine-root) {
                color: #0a1738;
                background: transparent;
                font-family: "Instrument Sans", Arial, sans-serif;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) h1,
            :is(#cb-bookingengine, .cb-bookingengine-root) h2,
            :is(#cb-bookingengine, .cb-bookingengine-root) h3,
            :is(#cb-bookingengine, .cb-bookingengine-root) h4,
            :is(#cb-bookingengine, .cb-bookingengine-root) h5,
            :is(#cb-bookingengine, .cb-bookingengine-root) h6 {
                color: #06112e;
                font-family: "Cormorant Garamond", Georgia, serif;
                font-weight: 500;
                letter-spacing: 0;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) button,
            :is(#cb-bookingengine, .cb-bookingengine-root) input,
            :is(#cb-bookingengine, .cb-bookingengine-root) select,
            :is(#cb-bookingengine, .cb-bookingengine-root) textarea {
                font-family: "Instrument Sans", Arial, sans-serif;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) button {
                border-radius: 10px;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) input,
            :is(#cb-bookingengine, .cb-bookingengine-root) select,
            :is(#cb-bookingengine, .cb-bookingengine-root) textarea {
                border-radius: 10px;
            }

            :is(#cb-bookingengine, .cb-bookingengine-root) a {
                color: #9a7529;
            }
        </style>
    </head>
    @php
        $currency = request()->attributes->get('currency', [
            'code' => 'USD',
            'symbol' => '$',
            'rate' => 1,
        ]);
        $cloudbedsPropertyCode = config('cloudbeds.property_code', 'ef9dzW');
    @endphp
    <body class="cloudbeds-booking-page">
        <x-site-page-header :currency="$currency" />

        <main class="cloudbeds-booking-main">
            <section class="cloudbeds-booking-heading" aria-labelledby="cloudbeds-booking-title">
                <p class="eyebrow">Maison Be Residences</p>
                <div class="cloudbeds-booking-heading-grid">
                    <h1 id="cloudbeds-booking-title">Reserve your stay.</h1>
                    <p>Select your dates, choose the residence that suits your stay, and complete your reservation securely without leaving Maison Be.</p>
                </div>
            </section>

            <section class="cloudbeds-booking-shell" aria-label="Maison Be booking engine">
                <div class="cloudbeds-booking-topline">
                    <div>
                        <span>Secure booking</span>
                        <strong>Powered by Cloudbeds</strong>
                    </div>
                    <a href="{{ route('apartments.index') }}">Explore residences</a>
                </div>

                <div class="cloudbeds-booking-embed">
                    <cb-immersive-experience
                        mode="standard"
                        property-code="{{ $cloudbedsPropertyCode }}"
                        currency="{{ strtoupper($currency['code'] ?? 'USD') }}"
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
        </main>

        <x-site-footer />
    </body>
</html>
