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
    $selfCheckInUrl = \Illuminate\Support\Facades\URL::signedRoute('reservations.self-check-in', $invoice);
    $formatGuestPhone = function (?string $value, ?string $country): string {
        $raw = trim((string) $value);
        if ($raw === '') return '';

        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        $dialByCountry = [
            'Nigeria' => '+234', 'United States' => '+1', 'Canada' => '+1', 'United Kingdom' => '+44',
            'Ghana' => '+233', 'South Africa' => '+27', 'United Arab Emirates' => '+971', 'UAE' => '+971',
            'Kenya' => '+254', 'Uganda' => '+256', 'Tanzania' => '+255', 'Rwanda' => '+250',
            'Ethiopia' => '+251', 'Egypt' => '+20', 'Morocco' => '+212', 'France' => '+33',
            'Germany' => '+49', 'Italy' => '+39', 'Spain' => '+34', 'Portugal' => '+351',
            'Netherlands' => '+31', 'Belgium' => '+32', 'Switzerland' => '+41', 'Ireland' => '+353',
            'Austria' => '+43', 'Sweden' => '+46', 'Norway' => '+47', 'Denmark' => '+45',
            'Finland' => '+358', 'Poland' => '+48', 'Turkey' => '+90', 'Saudi Arabia' => '+966',
            'Qatar' => '+974', 'Kuwait' => '+965', 'Bahrain' => '+973', 'Oman' => '+968',
            'India' => '+91', 'China' => '+86', 'Japan' => '+81', 'South Korea' => '+82',
            'Singapore' => '+65', 'Malaysia' => '+60', 'Indonesia' => '+62', 'Australia' => '+61',
            'New Zealand' => '+64', 'Brazil' => '+55', 'Mexico' => '+52',
        ];

        $dial = $dialByCountry[trim((string) $country)] ?? null;
        if ($dial) {
            $dialDigits = ltrim($dial, '+');
            if (str_starts_with($digits, $dialDigits)) {
                $national = substr($digits, strlen($dialDigits));
                $groups = match (strlen($national)) {
                    10 => [substr($national, 0, 3), substr($national, 3, 3), substr($national, 6, 4)],
                    9 => [substr($national, 0, 3), substr($national, 3, 3), substr($national, 6, 3)],
                    8 => [substr($national, 0, 4), substr($national, 4, 4)],
                    default => str_split($national, 3),
                };

                return trim($dial.' '.implode(' ', array_filter($groups, fn ($part) => $part !== '')));
            }
        }

        return $raw;
    };
    $guestPhone = $formatGuestPhone($invoice->phone, $invoice->country);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Reservation {{ $invoice->invoice }} | Maison Be</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        <x-site-page-header />
        <main class="receipt-main">
            <img src="{{ asset('brand/maison-be-logo-official.png') }}" alt="Maison Be Residences" style="display:block;width:170px;max-width:42vw;height:auto;margin:0 0 1.75rem;border-radius:10px;box-shadow:0 14px 34px rgba(6,17,46,.16);">
            <p class="eyebrow">{{ $invoice->payment_status === 'paid' ? 'Payment received' : 'Payment processing' }}</p>
            <h1>{{ $invoice->payment_status === 'paid' ? 'Booking confirmed.' : 'We are confirming your payment.' }}</h1>
            <p class="receipt-lead">Thank you, {{ $invoice->full_name }}. Your reservation reference is <strong>{{ $invoice->invoice }}</strong>.</p>
            <section class="receipt-card">
                <div class="receipt-property-block">
                    <h2>Property Address</h2>
                    @if ($address !== '')
                        <p>{{ $address }}</p>
                    @endif
                    <p><strong>Check-in Time:</strong> {{ $checkInTime }}</p>
                    <p><strong>Check-out Time:</strong> {{ $checkOutTime }}</p>
                    @if (filled($invoice->country))
                        <p class="receipt-country">Country: <strong>{{ $invoice->country }}</strong></p>
                    @endif
                    @if (filled($invoice->phone))
                        <p class="receipt-phone">Phone number: <strong>{{ $guestPhone }}</strong></p>
                    @endif
                </div>
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
            </section>
            @if ($invoice->serviceItems->isNotEmpty())
                <section class="receipt-card">
                    <p class="receipt-section-title">Additional services</p>
                    @foreach ($invoice->serviceItems as $serviceItem)
                        <div>
                            <span>{{ $serviceItem->name }} × {{ $serviceItem->quantity }}</span>
                            <strong>{{ $money($serviceItem->total) }}</strong>
                        </div>
                    @endforeach
                </section>
            @endif
            <section class="receipt-card">
                <p class="receipt-section-title">Receipt summary</p>
                <div><span>Subtotal</span><strong>{{ $money($invoice->subtotal) }}</strong></div>
                <div><span>{{ $couponLabel }}</span><strong>{{ $couponAmount }}</strong></div>
                <div class="receipt-total"><span>Total paid in {{ $invoice->currency_code }}</span><strong>{{ $money($invoice->total) }}</strong></div>
            </section>
            <p class="receipt-note"><strong>Note:</strong> You’re required to present a valid ID upon arrival to check-in. You can also self check-in using the secure link below to upload your ID.</p>
            <a class="receipt-self-checkin" href="{{ $selfCheckInUrl }}">Complete self check-in</a>
            <p class="receipt-note">{{ $invoice->payment_status === 'paid' ? 'This receipt confirms your instant booking at Maison Be Residences.' : 'Please allow a moment for payment confirmation. Refresh this page shortly if the status has not changed.' }}</p>
        </main>
        <x-site-footer />
    </body>
</html>
