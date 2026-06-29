@php
    $decimals = $invoice->currency_code === 'NGN' ? 0 : 2;
    $money = fn ($amount) => $invoice->currency.number_format((float) $amount, $decimals);
    $firstItem = $invoice->invoiceItems->first();
    $property = $firstItem?->apartment?->property;
    $address = collect([
        $property?->address,
        $property?->location_full_name,
        $property?->city,
        $property?->state,
        $property?->country,
    ])->filter()->unique()->values()->implode(', ');
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
                    <div><span>Residence</span><strong>{{ $item->name }}</strong></div>
                    @if ($address !== '')
                        <div><span>Property address</span><strong>{{ $address }}</strong></div>
                    @endif
                    <div><span>Check-in</span><strong>{{ $item->checkin->format('j M Y') }} at {{ $checkInTime }}</strong></div>
                    <div><span>Check-out</span><strong>{{ $item->checkout->format('j M Y') }} at {{ $checkOutTime }}</strong></div>
                    <div><span>{{ $item->quantity }} {{ \Illuminate\Support\Str::plural('night', $item->quantity) }}</span><strong>{{ $money($item->total) }}</strong></div>
                @endforeach
                <div><span>Subtotal</span><strong>{{ $money($invoice->subtotal) }}</strong></div>
                @if ((float) $invoice->discount > 0)
                    <div><span>Coupon {{ $invoice->coupon_code }}</span><strong>-{{ $money($invoice->discount) }}</strong></div>
                @endif
                <div class="receipt-total"><span>Total paid in {{ $invoice->currency_code }}</span><strong>{{ $money($invoice->total) }}</strong></div>
            </section>
            <p class="receipt-note">{{ $invoice->payment_status === 'paid' ? 'This receipt confirms your instant booking at Maison Be Residences.' : 'Please allow a moment for payment confirmation. Refresh this page shortly if the status has not changed.' }}</p>
        </main>
        <x-site-footer />
    </body>
</html>
