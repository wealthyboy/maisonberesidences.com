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
                                            <img src="{{ asset('brand/maison-be-logo.png') }}" alt="Maison Be Residences logo" width="108" style="display:block;width:108px;height:auto;margin:0 0 14px;border-radius:8px;">
                                            <p style="margin:0;color:#a78135;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Maison Be Residences</p>
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
                                            @if (filled($invoice->phone))
                                                <p style="margin:8px 0 0;color:#425065;">Phone number: <strong>{{ $invoice->phone }}</strong></p>
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

                                <p style="margin:24px 0 0;color:#5e6678;font-size:14px;line-height:1.6;"><strong>Note:</strong> You’re required to present a valid ID upon arrival to check-in. You can also self check-in by clicking the link below to upload your ID.</p>
                                <p style="margin:24px 0 0;color:#5e6678;font-size:14px;line-height:1.6;">This receipt confirms your instant booking at Maison Be Residences.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
