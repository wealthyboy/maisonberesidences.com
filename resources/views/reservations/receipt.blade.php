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
            <p class="eyebrow">{{ $invoice->payment_status === 'paid' ? 'Payment received' : 'Payment processing' }}</p>
            <h1>{{ $invoice->payment_status === 'paid' ? 'Booking confirmed.' : 'We are confirming your payment.' }}</h1>
            <p class="receipt-lead">Thank you, {{ $invoice->full_name }}. Your reservation reference is <strong>{{ $invoice->invoice }}</strong>.</p>
            <section class="receipt-card">
                @foreach ($invoice->invoiceItems as $item)
                    <div><span>Residence</span><strong>{{ $item->name }}</strong></div>
                    <div><span>Stay</span><strong>{{ $item->checkin->format('j M Y') }} - {{ $item->checkout->format('j M Y') }}</strong></div>
                    <div><span>{{ $item->quantity }} {{ \Illuminate\Support\Str::plural('night', $item->quantity) }}</span><strong>{{ $invoice->currency }}{{ number_format((float) $item->total, $invoice->currency_code === 'NGN' ? 0 : 2) }}</strong></div>
                @endforeach
                @if ((float) $invoice->discount > 0)
                    <div><span>Coupon {{ $invoice->coupon_code }}</span><strong>-{{ $invoice->currency }}{{ number_format((float) $invoice->discount, $invoice->currency_code === 'NGN' ? 0 : 2) }}</strong></div>
                @endif
                <div class="receipt-total"><span>Total paid in {{ $invoice->currency_code }}</span><strong>{{ $invoice->currency }}{{ number_format((float) $invoice->total, $invoice->currency_code === 'NGN' ? 0 : 2) }}</strong></div>
            </section>
            <p class="receipt-note">{{ $invoice->payment_status === 'paid' ? 'This receipt confirms your instant booking at Maison Be Residences.' : 'Please allow a moment for payment confirmation. Refresh this page shortly if the status has not changed.' }}</p>
        </main>
        <x-site-footer />
    </body>
</html>
