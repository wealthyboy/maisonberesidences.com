@php
    $decimals = $invoice->currency_code === 'NGN' ? 0 : 2;
    $money = fn ($amount) => $invoice->currency.number_format((float) $amount, $decimals);
    $firstItem = $invoice->invoiceItems->first();
    $apartment = $firstItem?->apartment;
    $property = $apartment?->property;
    $resolveImage = function (?string $path): ?string {
        if (! filled($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset($path);
    };
    $apartmentImage = $apartment?->images?->map(fn ($image) => $resolveImage($image->image))->filter()->first()
        ?: ($resolveImage($apartment?->image) ?: asset('media/maisonbe-hero-source.jpg'));
    $address = collect([
        $property?->address,
        $property?->location_full_name,
        $property?->city,
        $property?->state,
        $property?->country,
    ])->filter()->unique()->values()->implode(', ') ?: (string) $invoice->address;
    $time = function ($value, string $fallback): string {
        if (! filled($value)) return $fallback;

        try {
            return \Carbon\Carbon::parse((string) $value)->format('g:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $checkInTime = $time($property?->check_in_time, '2:00 PM');
    $checkOutTime = $time($property?->check_out_time, '12:00 PM');
    $couponLabel = filled($invoice->coupon_code) ? 'Coupon '.$invoice->coupon_code : 'Coupon';
    $couponAmount = (float) $invoice->discount > 0 ? '-'.$money($invoice->discount) : $money(0);
    $selfCheckInUrl = 'mailto:info@maisonberesidences.com?subject='.rawurlencode('Self check-in ID for '.$invoice->invoice);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Reservation {{ $invoice->invoice }} | Maison Be</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        <header class="results-header"><a class="results-wordmark" href="{{ url('/') }}">Maison Be <small>Residences</small></a></header>
        <main class="receipt-main">
            <img src="{{ asset('brand/maison-mb-mark.png') }}" alt="Maison Be Residences" style="width: 82px; height: auto; margin-bottom: 1.2rem;">
            <p class="eyebrow">{{ $invoice->payment_status === 'paid' ? 'Payment received' : 'Payment processing' }}</p>
            <h1>{{ $invoice->payment_status === 'paid' ? 'Booking confirmed.' : 'We are confirming your payment.' }}</h1>
            <p class="receipt-lead">Thank you, {{ $invoice->full_name }}. Your reservation reference is <strong>{{ $invoice->invoice }}</strong>.</p>
            <section class="receipt-card">
                @foreach ($invoice->invoiceItems as $item)
                    <div class="receipt-stay-card">
                        <img src="{{ $apartmentImage }}" alt="{{ $item->name }} at Maison Be" loading="lazy" decoding="async">
                        <div>
                            <h2>{{ $item->name }}</h2>
                            <p><strong>Check-in :</strong> {{ $item->checkin->format('l, F jS Y') }}</p>
                            <p><strong>Check-out:</strong> {{ $item->checkout->format('l, F jS Y') }}</p>
                            <p><strong>Length of stay:</strong> {{ $item->quantity }} {{ \Illuminate\Support\Str::plural('night', $item->quantity) }}</p>
                            <p><strong>{{ $money($item->price) }} per night</strong></p>
                        </div>
                    </div>
                @endforeach
                <div class="receipt-property-block">
                    <h2>Property Address</h2>
                    @if ($address !== '')
                        <p>{{ $address }}</p>
                    @endif
                    <p><strong>Check-in Time:</strong> {{ $checkInTime }}</p>
                    <p><strong>Check-out Time:</strong> {{ $checkOutTime }}</p>
                    @if (filled($invoice->phone))
                        <p class="receipt-phone">Phone number: <strong>{{ $invoice->phone }}</strong></p>
                    @endif
                </div>
            </section>
            <section class="receipt-card">
                <p class="receipt-section-title">Receipt summary</p>
                <div><span>Subtotal</span><strong>{{ $money($invoice->subtotal) }}</strong></div>
                <div><span>{{ $couponLabel }}</span><strong>{{ $couponAmount }}</strong></div>
                <div class="receipt-total"><span>Total paid in {{ $invoice->currency_code }}</span><strong>{{ $money($invoice->total) }}</strong></div>
            </section>
            <p class="receipt-note"><strong>Note:</strong> You’re required to present a valid ID upon arrival to check-in. You can also self check-in by clicking the link below to upload your ID.</p>
            <a class="receipt-id-link" href="{{ $selfCheckInUrl }}">Upload your ID</a>
            <p class="receipt-note">{{ $invoice->payment_status === 'paid' ? 'This receipt confirms your instant booking at Maison Be Residences.' : 'Please allow a moment for payment confirmation. Refresh this page shortly if the status has not changed.' }}</p>
        </main>
        <x-site-footer />
    </body>
</html>
