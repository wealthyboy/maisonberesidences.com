@php
    $decimals = $invoice->currency_code === 'NGN' ? 0 : 2;
    $money = fn ($amount) => $invoice->currency.number_format((float) $amount, $decimals);
    $item = $invoice->invoiceItems->first();
    $apartment = $item?->apartment;
    $property = $apartment?->property;
    $resolveImage = function (?string $path): ?string {
        if (! filled($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset($path);
    };
    $apartmentImage = $apartment?->images?->map(fn ($image) => $resolveImage($image->image))->filter()->first()
        ?: ($resolveImage($apartment?->image) ?: asset('media/maisonbe-hero-source.jpg'));
    $addressParts = collect([
        $property?->address,
        $property?->location_full_name,
        $property?->city,
        $property?->state,
        $property?->country,
    ])->filter()->unique()->values();
    $address = $addressParts->implode(', ') ?: (string) $invoice->address;
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
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Your Maison Be booking is confirmed</title>
    </head>
    <body style="margin:0;background:#f8f4ec;color:#06112e;font-family:Arial,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f4ec;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#fffdf8;border:1px solid #d8cba9;border-radius:8px;padding:30px;">
                        <tr>
                            <td>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td>
                                            <img src="{{ asset('brand/maison-be-logo-official.png') }}" alt="Maison Be Residences logo" width="150" style="display:block;width:150px;max-width:100%;height:auto;margin:0;border-radius:10px;">
                                        </td>
                                        <td align="right" style="color:#5e6678;font-size:13px;line-height:1.5;">
                                            Reservation<br>
                                            <strong style="color:#06112e;">{{ $invoice->invoice }}</strong>
                                        </td>
                                    </tr>
                                </table>

                                <h1 style="margin:24px 0 14px;color:#06112e;font-size:32px;line-height:1.05;">Booking Confirmed</h1>
                                <p style="margin:0 0 26px;color:#5e6678;font-size:15px;line-height:1.6;">Thank you, {{ $invoice->full_name }}. Your instant booking is confirmed.</p>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d8cba9;border-bottom:1px solid #d8cba9;">
                                    <tr>
                                        <td style="padding:18px 0;color:#303030;font-size:14px;line-height:1.5;">
                                            <p style="margin:0 0 5px;color:#425065;font-size:17px;font-weight:800;">Property Address</p>
                                            @if ($address !== '')
                                                <p style="margin:0 0 5px;">{{ $address }}</p>
                                            @endif
                                            <p style="margin:0;"><strong style="font-size:15px;">Check-in Time:</strong> {{ $checkInTime }}</p>
                                            <p style="margin:3px 0 0;"><strong style="font-size:15px;">Check-out Time:</strong> {{ $checkOutTime }}</p>
                                            @if (filled($invoice->country))
                                                <p style="margin:8px 0 0;color:#425065;">Country: <strong>{{ $invoice->country }}</strong></p>
                                            @endif
                                            @if (filled($invoice->phone))
                                                <p style="margin:3px 0 0;color:#425065;">Phone number: <strong>{{ $guestPhone }}</strong></p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-bottom:1px solid #d8cba9;">
                                    <tr>
                                        <td style="padding:20px 0;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td width="164" valign="top" style="padding-right:16px;">
                                                        <img src="{{ $apartmentImage }}" alt="{{ $item?->name ?: $apartment?->name }}" width="154" style="display:block;width:154px;max-width:100%;height:auto;border:0;">
                                                    </td>
                                                    <td valign="top" style="color:#424242;font-size:14px;line-height:1.5;">
                                                        <p style="margin:0 0 5px;color:#2fb49d;font-size:16px;font-weight:800;letter-spacing:1.6px;text-transform:uppercase;">{{ $item?->name ?: $apartment?->name }}</p>
                                                        <p style="margin:0;"><strong>Check-in :</strong> {{ optional($item?->checkin)->format('l, F jS Y') }}</p>
                                                        <p style="margin:0;"><strong>Check-out:</strong> {{ optional($item?->checkout)->format('l, F jS Y') }}</p>
                                                        <p style="margin:0;"><strong>Length of stay:</strong> {{ $item?->quantity }} {{ \Illuminate\Support\Str::plural('night', (int) $item?->quantity) }}</p>
                                                        <p style="margin:4px 0 0;font-weight:800;">{{ $money($item?->price) }} per night</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                @if ($invoice->serviceItems->isNotEmpty())
                                    <p style="margin:28px 0 10px;color:#a78135;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Additional services</p>
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d8cba9;border-bottom:1px solid #d8cba9;">
                                        @foreach ($invoice->serviceItems as $serviceItem)
                                            <tr>
                                                <td style="padding:12px 0;color:#5e6678;">{{ $serviceItem->name }} × {{ $serviceItem->quantity }}</td>
                                                <td align="right" style="padding:12px 0;font-weight:700;">{{ $money($serviceItem->total) }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif

                                <p style="margin:28px 0 10px;color:#a78135;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Receipt summary</p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d8cba9;border-bottom:1px solid #d8cba9;">
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;">Subtotal</td>
                                        <td align="right" style="padding:14px 0;font-weight:700;">{{ $money($invoice->subtotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 0;color:#2f855a;border-top:1px solid #efe6d1;">{{ $couponLabel }}</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;color:#2f855a;font-weight:700;">{{ $couponAmount }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:18px 0;color:#5e6678;border-top:1px solid #d8cba9;">Total paid in {{ $invoice->currency_code }}</td>
                                        <td align="right" style="padding:18px 0;border-top:1px solid #d8cba9;font-size:24px;font-weight:700;">{{ $money($invoice->total) }}</td>
                                    </tr>
                                </table>

                                <p style="margin:24px 0 0;color:#5e6678;font-size:14px;line-height:1.6;"><strong>Note:</strong> You’re required to present a valid ID upon arrival to check-in. You can also self check-in using the secure link below to upload your ID.</p>
                                <p style="margin:18px 0 0;"><a href="{{ $selfCheckInUrl }}" style="display:inline-block;padding:14px 20px;border-radius:7px;color:#fff;background:#06112e;font-size:12px;font-weight:700;letter-spacing:1px;text-decoration:none;text-transform:uppercase;">Complete self check-in</a></p>
                                <p style="margin:24px 0 0;color:#5e6678;font-size:14px;line-height:1.6;">This receipt confirms your instant booking at Maison Be Residences.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
