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
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fffdf8;border:1px solid #d8cba9;border-radius:8px;padding:28px;">
                        <tr>
                            <td>
                                <p style="margin:0 0 12px;color:#a78135;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Maison Be Residences</p>
                                <h1 style="margin:0 0 16px;color:#18264a;font-size:30px;line-height:1.1;">Booking Confirmed</h1>
                                <p style="margin:0 0 24px;color:#5e6678;font-size:15px;line-height:1.6;">Thank you, {{ $invoice->full_name }}. Your instant booking is confirmed.</p>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d8cba9;border-bottom:1px solid #d8cba9;">
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;">Reservation reference</td>
                                        <td align="right" style="padding:14px 0;font-weight:700;">{{ $invoice->invoice }}</td>
                                    </tr>
                                    @foreach ($invoice->invoiceItems as $item)
                                        <tr>
                                            <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Apartment</td>
                                            <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-weight:700;">{{ $item->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Stay</td>
                                            <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;">{{ optional($item->checkin)->format('M j, Y') }} - {{ optional($item->checkout)->format('M j, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Nights</td>
                                            <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;">{{ $item->quantity }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td style="padding:14px 0;color:#5e6678;border-top:1px solid #efe6d1;">Total paid</td>
                                        <td align="right" style="padding:14px 0;border-top:1px solid #efe6d1;font-size:22px;font-weight:700;">{{ $invoice->currency }}{{ number_format((float) $invoice->total, $invoice->currency_code === 'NGN' ? 0 : 2) }}</td>
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
