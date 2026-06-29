@php
    $decimals = $invoice->currency_code === 'NGN' ? 0 : 2;
    $money = fn ($amount) => $invoice->currency.number_format((float) $amount, $decimals);
    $item = $invoice->invoiceItems->first();
    $apartment = $item?->apartment;
    $property = $apartment?->property;
    $addressParts = collect([
        $property?->address,
        $property?->location_full_name,
        $property?->city,
        $property?->state,
        $property?->country,
    ])->filter()->unique()->values();
    $address = $addressParts->implode(', ');
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
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Your Maison Be booking is confirmed</title>
    </head>
    <body style="margin:0;background:#f8f4ec;color:#18264a;font-family:Arial,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f4ec;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#fffdf8;border:1px solid #d8cba9;border-radius:8px;padding:30px;">
                        <tr>
                            <td>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td>
                                            <img src="{{ asset('brand/maison-mb-mark.png') }}" alt="Maison Be Residences" width="74" style="display:block;width:74px;height:auto;margin:0 0 14px;">
                                            <p style="margin:0;color:#a78135;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Maison Be Residences</p>
                                        </td>
                                        <td align="right" style="color:#5e6678;font-size:13px;line-height:1.5;">
                                            Reservation<br>
                                            <strong style="color:#18264a;">{{ $invoice->invoice }}</strong>
                                        </td>
                                    </tr>
                                </table>

                                <h1 style="margin:24px 0 14px;color:#18264a;font-size:32px;line-height:1.05;">Booking Confirmed</h1>
                                <p style="margin:0 0 26px;color:#5e6678;font-size:15px;line-height:1.6;">Thank you, {{ $invoice->full_name }}. Your instant booking is confirmed.</p>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d8cba9;border-bottom:1px solid #d8cba9;">
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;">Apartment</td>
                                        <td align="right" style="padding:14px 0;font-weight:700;">{{ $item?->name ?: $apartment?->name }}</td>
                                    </tr>
                                    @if ($address !== '')
                                        <tr>
                                            <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Property address</td>
                                            <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-weight:700;line-height:1.45;">{{ $address }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Check-in</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-weight:700;">{{ optional($item?->checkin)->format('M j, Y') }} at {{ $checkInTime }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Check-out</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-weight:700;">{{ optional($item?->checkout)->format('M j, Y') }} at {{ $checkOutTime }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Nights</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;">{{ $item?->quantity }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Subtotal</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-weight:700;">{{ $money($invoice->subtotal) }}</td>
                                    </tr>
                                    @if ((float) $invoice->discount > 0)
                                        <tr>
                                            <td style="padding:14px 0;color:#2f855a;border-top:1px solid #efe6d1;">Coupon {{ $invoice->coupon_code }}</td>
                                            <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;color:#2f855a;font-weight:700;">-{{ $money($invoice->discount) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding:18px 0;color:#5e6678;border-top:1px solid #d8cba9;">Total paid in {{ $invoice->currency_code }}</td>
                                        <td align="right" style="padding:18px 0;border-top:1px solid #d8cba9;font-size:24px;font-weight:700;">{{ $money($invoice->total) }}</td>
                                    </tr>
                                </table>

                                <p style="margin:24px 0 0;color:#5e6678;font-size:14px;line-height:1.6;">This receipt confirms your instant booking at Maison Be Residences.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
