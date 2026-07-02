@php
    $resolveApartmentImage = function (?string $path) {
        if (! filled($path)) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) $path = substr($path, 7);

        return asset($path);
    };

    $checkoutImage = $apartment->images->map(fn ($image) => $resolveApartmentImage($image->image))->filter()->first()
        ?: ($resolveApartmentImage($apartment->image) ?: asset('media/maisonbe-hero-source.jpg'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Review and book {{ $apartment->name }} | Maison Be</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        <script src="https://js.paystack.co/v2/inline.js" async data-payment-library onload="this.dataset.loaded='true'" onerror="this.dataset.failed='true'"></script>
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page checkout-page">
        <header class="results-header">
            <a class="results-wordmark" href="{{ url('/') }}">Maison Be <small>Residences</small></a>
            <a href="{{ route('apartments.show', ['apartment' => $apartment, 'checkin' => $stay['checkin']->toDateString(), 'checkout' => $stay['checkout']->toDateString()]) }}" class="results-back">Back to residence</a>
        </header>

        <main class="checkout-main">
            <section class="booking-confirmed" hidden data-booking-confirmed>
                <div class="booking-confirmed-mark">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>
                </div>
                <p class="eyebrow">Maison Be reservations</p>
                <h1>Booking Confirmed</h1>
                <p>Thank you for your payment. Your booking is confirmed instantly. We look forward to hosting you at Maison Be Residences.</p>
                <p class="booking-confirmed-reference" data-booking-reference></p>
                <div class="booking-confirmed-actions">
                    <a href="#" hidden data-receipt-link>View receipt</a>
                    <a href="{{ url('/') }}">Return home</a>
                </div>
            </section>

            <div class="checkout-heading" data-checkout-panel>
                <p class="eyebrow">Maison Be reservations</p>
                <h1>Review and book.</h1>
                <p>Confirm your details, review your total, then complete payment securely.</p>
            </div>

            @if ($errors->any())
                <p class="checkout-error">{{ $errors->first() }}</p>
            @endif

            <div class="checkout-layout" data-checkout-panel>
                <form class="checkout-form" method="post" action="{{ route('reservations.store', $apartment) }}">
                    @csrf
                    <input type="hidden" name="checkin" value="{{ $stay['checkin']->toDateString() }}">
                    <input type="hidden" name="checkout" value="{{ $stay['checkout']->toDateString() }}">

                    <section class="checkout-section checkout-contact">
                        <div class="checkout-section-heading">
                            <span>1</span>
                            <div>
                                <h2>Guest details</h2>
                                <p>We will use these details for your reservation receipt and arrival coordination.</p>
                            </div>
                        </div>

                        <div class="checkout-fields">
                            <label>
                                First name
                                <input name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required>
                            </label>
                            <label>
                                Last name
                                <input name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required>
                            </label>
                            <label class="checkout-field-wide">
                                Email address
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                            </label>
                            <label>
                                Country code
                                <select name="country_code" autocomplete="tel-country-code" required>
                                    @foreach (['+234' => 'Nigeria +234', '+1' => 'USA/Canada +1', '+44' => 'United Kingdom +44', '+233' => 'Ghana +233', '+27' => 'South Africa +27', '+971' => 'UAE +971'] as $code => $label)
                                        <option value="{{ $code }}" @selected(old('country_code', $quote['currency']['code'] === 'NGN' ? '+234' : '+1') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                Phone number
                                <input name="phone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel-national" placeholder="801 234 5678" required>
                            </label>
                            <label class="checkout-field-wide">
                                Country
                                <input name="country" value="{{ old('country', $quote['currency']['country'] ?: 'Nigeria') }}" autocomplete="country-name">
                            </label>
                        </div>
                    </section>

                    <section class="checkout-section checkout-rules">
                        <div class="checkout-section-heading">
                            <span>2</span>
                            <div>
                                <h2>Stay information</h2>
                                <p>Please review the house rules before continuing.</p>
                            </div>
                        </div>
                        <div class="checkout-rule-grid">
                            <div><strong>Check-in</strong><span>From 2:00 PM</span></div>
                            <div><strong>Check-out</strong><span>By 12:00 PM</span></div>
                        </div>
                        <p>By continuing, you acknowledge that you have read and understand the rules and regulations of this residence.</p>
                    </section>

                    <section class="checkout-section checkout-coupon">
                        <div class="checkout-section-heading">
                            <span>3</span>
                            <div>
                                <h2>Coupon</h2>
                                <p>Apply a valid coupon before making payment.</p>
                            </div>
                        </div>
                        <div class="checkout-coupon-row">
                            <label>
                                Coupon code
                                <input name="coupon_code" value="{{ old('coupon_code') }}" placeholder="MAISONBE10" data-coupon-input>
                            </label>
                            <button type="button" data-coupon-apply>Apply</button>
                        </div>
                        <p class="checkout-coupon-status" aria-live="polite" data-coupon-status></p>
                    </section>

                    <section class="checkout-summary-card checkout-price-card">
                        <div class="checkout-apartment-preview">
                            <img src="{{ $checkoutImage }}" alt="{{ $apartment->name }} at Maison Be" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
                            <div>
                                <p class="eyebrow">Selected apartment</p>
                                <h2>{{ $apartment->name }}</h2>
                            </div>
                        </div>
                        <p class="eyebrow">Price details</p>
                        <div class="checkout-price-line">
                            <span>{{ $quote['display_nightly'] }} × {{ $quote['nights'] }} {{ \Illuminate\Support\Str::plural('night', $quote['nights']) }}<small>per night</small></span>
                            <strong>{{ $quote['display_total'] }}</strong>
                        </div>
                        <div class="checkout-price-line checkout-discount-line" hidden data-discount-line>
                            <span>Coupon discount<small data-discount-code></small></span>
                            <strong>-<span data-discount-amount>{{ $quote['currency']['symbol'] }}0</span></strong>
                        </div>
                        @if($quote['peak_nights'])
                            <p class="checkout-peak">{{ $quote['peak_nights'] }} nights include peak-period pricing.</p>
                        @endif
                        <div class="checkout-total"><span>Total price</span><strong data-checkout-total>{{ $quote['display_total'] }}</strong></div>
                    </section>

                    <p class="payment-marks">
                        <strong>Accepted payment</strong>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18"></path></svg>Visa</span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="12" r="4"></circle><circle cx="15" cy="12" r="4"></circle></svg>Mastercard</span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 8 4 8 4-8"></path><path d="M15 8h4"></path><path d="M17 8v8"></path></svg>Verve</span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M7 15h4"></path><path d="M3 10h18"></path></svg>Bank card</span>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16"></path><path d="m6 10 6-5 6 5"></path><path d="M7 10v7"></path><path d="M12 10v7"></path><path d="M17 10v7"></path><path d="M5 17h14"></path></svg>Bank transfer</span>
                    </p>
                    <p class="checkout-payment-status" aria-live="polite" data-payment-status></p>
                    <button class="checkout-submit" type="submit" data-payment-submit>
                        <span data-payment-label>Make payment</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <aside class="checkout-summary">
                    <section class="checkout-summary-card">
                        <div class="checkout-stay-media">
                            <img src="{{ $checkoutImage }}" alt="{{ $apartment->name }} at Maison Be" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
                            <div>
                                <p class="eyebrow">Your stay</p>
                                <h2>{{ $apartment->name }}</h2>
                                <p class="checkout-stay-nightly">{{ $quote['display_nightly'] }} <span>per night</span></p>
                            </div>
                        </div>
                        <dl>
                            <div><dt>Check in</dt><dd>{{ $stay['checkin']->format('D, j M Y') }}</dd></div>
                            <div><dt>Check out</dt><dd>{{ $stay['checkout']->format('D, j M Y') }}</dd></div>
                            <div><dt>Total nights</dt><dd>{{ $quote['nights'] }} {{ \Illuminate\Support\Str::plural('night', $quote['nights']) }}</dd></div>
                        </dl>
                    </section>
                </aside>
            </div>
        </main>

        <x-site-footer />
        <script>
            (() => {
                const input = document.querySelector('[data-coupon-input]');
                const apply = document.querySelector('[data-coupon-apply]');
                const status = document.querySelector('[data-coupon-status]');
                const discountLine = document.querySelector('[data-discount-line]');
                const discountCode = document.querySelector('[data-discount-code]');
                const discountAmount = document.querySelector('[data-discount-amount]');
                const total = document.querySelector('[data-checkout-total]');
                const form = document.querySelector('.checkout-form');

                if (!input || !apply || !status || !discountLine || !discountAmount || !total || !form) return;

                apply.addEventListener('click', async () => {
                    status.textContent = 'Checking coupon...';
                    apply.disabled = true;

                    try {
                        const body = new FormData();
                        body.set('checkin', form.querySelector('[name="checkin"]').value);
                        body.set('checkout', form.querySelector('[name="checkout"]').value);
                        body.set('coupon_code', input.value);

                        const response = await fetch('{{ route('reservations.coupon', $apartment) }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body,
                        });
                        const result = await response.json();

                        if (!response.ok) throw new Error(result.message || 'Coupon could not be applied.');

                        discountLine.hidden = !result.coupon;
                        discountCode.textContent = result.coupon || '';
                        discountAmount.textContent = result.display_discount;
                        total.textContent = result.display_total;
                        status.textContent = result.message;
                    } catch (error) {
                        discountLine.hidden = true;
                        total.textContent = '{{ $quote['display_total'] }}';
                        status.textContent = error.message;
                    } finally {
                        apply.disabled = false;
                    }
                });
            })();

            (() => {
                const form = document.querySelector('.checkout-form');
                const submit = document.querySelector('[data-payment-submit]');
                const label = document.querySelector('[data-payment-label]');
                const status = document.querySelector('[data-payment-status]');
                const confirmation = document.querySelector('[data-booking-confirmed]');
                const reference = document.querySelector('[data-booking-reference]');
                const receiptLink = document.querySelector('[data-receipt-link]');
                const panels = document.querySelectorAll('[data-checkout-panel]');

                if (!form || !submit || !label || !status) return;

                const loadScript = () => new Promise((resolve, reject) => {
                    const timeout = window.setTimeout(() => {
                        reject(new Error('Payment window is taking too long to load. Please refresh and try again.'));
                    }, 15000);

                    const done = () => {
                        window.clearTimeout(timeout);
                        resolve();
                    };

                    const fail = () => {
                        window.clearTimeout(timeout);
                        reject(new Error('Payment window could not load. Please check your connection and try again.'));
                    };

                    if (window.PaystackPop || window.Paystack) {
                        done();
                        return;
                    }

                    const existingScript = document.querySelector('[data-payment-library]');
                    if (existingScript) {
                        if (existingScript.dataset.loaded === 'true') {
                            if (window.PaystackPop || window.Paystack) {
                                done();
                                return;
                            }

                            existingScript.remove();
                        } else {
                            existingScript.addEventListener('load', done, { once: true });
                            existingScript.addEventListener('error', fail, { once: true });
                            return;
                        }
                    }

                    const existingPaystackScript = document.querySelector('script[src="https://js.paystack.co/v2/inline.js"]');
                    if (existingPaystackScript) {
                        existingPaystackScript.addEventListener('load', done, { once: true });
                        existingPaystackScript.addEventListener('error', fail, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://js.paystack.co/v2/inline.js';
                    script.async = true;
                    script.dataset.paymentLibrary = '';
                    document.getElementsByTagName('head')[0].appendChild(script);

                    if (script.readyState) {
                        script.onreadystatechange = () => {
                            if (script.readyState === 'loaded' || script.readyState === 'complete') {
                                script.onreadystatechange = null;
                                done();
                            }
                        };
                    } else {
                        script.onload = () => done();
                    }
                    script.onerror = fail;
                });

                const setProcessing = (isProcessing, text = 'Make payment') => {
                    submit.disabled = isProcessing;
                    label.textContent = text;
                };

                const errorMessage = (payload) => {
                    if (payload?.errors) {
                        const first = Object.values(payload.errors)[0];
                        if (Array.isArray(first) && first.length) return first[0];
                    }

                    return payload?.message || 'Payment could not be started. Please check your details and try again.';
                };

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!form.reportValidity()) return;

                    setProcessing(true, 'Processing...');
                    status.textContent = 'Preparing your payment...';

                    try {
                        const controller = new AbortController();
                        const timeout = window.setTimeout(() => controller.abort(), 20000);
                        let response;

                        try {
                            response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: new FormData(form),
                                signal: controller.signal,
                            });
                        } finally {
                            window.clearTimeout(timeout);
                        }

                        const payload = await response.json();

                        if (!response.ok) throw new Error(errorMessage(payload));

                        await loadScript();

                        const payment = payload.payment;
                        const confirmBooking = async (response) => {
                            const paymentReference = response?.reference || response?.trxref || payment.reference;
                            status.textContent = 'Confirming your booking...';

                            const confirmationResponse = await fetch('{{ route('reservations.payment-confirm') }}', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    reference: paymentReference,
                                    response,
                                }),
                            });
                            const confirmationPayload = await confirmationResponse.json();

                            if (!confirmationResponse.ok) {
                                throw new Error(errorMessage(confirmationPayload));
                            }

                            return confirmationPayload;
                        };

                        const showConfirmation = async (response) => {
                            let confirmedReference = response?.reference || response?.trxref || payment.reference;
                            let confirmedReceiptUrl = payment.receipt_url;

                            try {
                                const confirmed = await confirmBooking(response);
                                confirmedReference = confirmed.reference || confirmedReference;
                                confirmedReceiptUrl = confirmed.receipt_url || confirmedReceiptUrl;
                                sessionStorage.setItem('maisonbe_booking_confirmed', confirmedReference);
                                status.textContent = 'Booking confirmed.';
                            } catch (error) {
                                setProcessing(false);
                                status.textContent = error.message || 'Payment was received, but booking confirmation is still pending. Please contact Maison Be Reservations with your payment reference.';
                                return;
                            }

                            panels.forEach((panel) => {
                                panel.hidden = true;
                                panel.style.display = 'none';
                            });
                            if (reference) {
                                reference.textContent = confirmedReference ? `Payment reference: ${confirmedReference}` : '';
                            }
                            if (receiptLink && confirmedReceiptUrl) {
                                receiptLink.href = confirmedReceiptUrl;
                                receiptLink.hidden = false;
                            }
                            if (confirmation) {
                                confirmation.hidden = false;
                                confirmation.style.display = 'grid';
                            }
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        };
                        const handleClose = () => {
                            setProcessing(false);
                            status.textContent = 'Payment window closed. You can try again when ready.';
                        };

                        if (window.PaystackPop && typeof PaystackPop.setup === 'function') {
                            const handler = PaystackPop.setup({
                                key: payment.key,
                                email: payment.email,
                                amount: payment.amount,
                                currency: payment.currency,
                                ref: payment.reference,
                                first_name: payment.first_name,
                                last_name: payment.last_name,
                                metadata: payment.metadata,
                                callback: showConfirmation,
                                onClose: handleClose,
                            });

                            handler.openIframe();
                            status.textContent = 'Complete your payment in the secure window.';
                            return;
                        }

                        if (window.PaystackPop) {
                            const popup = new PaystackPop();
                            popup.newTransaction({
                                key: payment.key,
                                email: payment.email,
                                amount: payment.amount,
                                currency: payment.currency,
                                reference: payment.reference,
                                ref: payment.reference,
                                metadata: payment.metadata,
                                onSuccess: showConfirmation,
                                callback: showConfirmation,
                                onCancel: handleClose,
                                onClose: handleClose,
                            });
                            status.textContent = 'Complete your payment in the secure window.';
                            return;
                        }

                        if (window.Paystack && typeof Paystack === 'function') {
                            const popup = new Paystack();
                            if (typeof popup.newTransaction === 'function') {
                                popup.newTransaction({
                                    key: payment.key,
                                    email: payment.email,
                                    amount: payment.amount,
                                    currency: payment.currency,
                                    reference: payment.reference,
                                    ref: payment.reference,
                                    metadata: payment.metadata,
                                    onSuccess: showConfirmation,
                                    callback: showConfirmation,
                                    onCancel: handleClose,
                                    onClose: handleClose,
                                });
                                status.textContent = 'Complete your payment in the secure window.';
                                return;
                            }
                        }

                        if (payment.authorization_url) {
                            window.location.href = payment.authorization_url;
                            return;
                        }

                        if (!window.PaystackPop && !window.Paystack) {
                            throw new Error('Payment window is not available yet. Please try again.');
                        }

                        throw new Error('Payment window loaded, but could not start. Please refresh and try again.');
                    } catch (error) {
                        setProcessing(false);
                        status.textContent = error.name === 'AbortError'
                            ? 'Payment is taking too long to start. Please try again.'
                            : error.message;
                    }
                });

                sessionStorage.removeItem('maisonbe_booking_confirmed');
            })();
        </script>
    </body>
</html>
