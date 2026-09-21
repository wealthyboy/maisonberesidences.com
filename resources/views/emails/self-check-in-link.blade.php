<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Your self check-in link</title>
    </head>
    <body style="margin:0;background:#f8f4ec;color:#06112e;font-family:Arial,sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f4ec;padding:32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fffdf8;border:1px solid #d8cba9;border-radius:8px;padding:30px;">
                        <tr>
                            <td>
                                <img src="{{ asset('brand/maison-be-logo-official.png') }}" alt="Maison Be Residences" width="150" style="display:block;width:150px;max-width:100%;height:auto;border:0;border-radius:10px;">
                                <h1 style="margin:26px 0 12px;font-size:30px;line-height:1.1;">Complete your self check-in</h1>
                                <p style="margin:0 0 14px;color:#5e6678;font-size:15px;line-height:1.65;">Hello {{ $details['first_name'] ?: $invoice->full_name }}, use the secure link below to upload your ID for reservation <strong>{{ $invoice->invoice }}</strong>.</p>
                                @if ($details['checkin'] && $details['checkout'])
                                    <p style="margin:0 0 22px;color:#5e6678;font-size:14px;line-height:1.6;">{{ $details['apartment'] }} · {{ $details['checkin']->format('M j, Y') }} to {{ $details['checkout']->format('M j, Y') }}</p>
                                @endif
                                <p style="margin:0 0 24px;"><a href="{{ $selfCheckInUrl }}" style="display:inline-block;padding:14px 20px;border-radius:7px;color:#fff;background:#06112e;font-size:12px;font-weight:700;letter-spacing:1px;text-decoration:none;text-transform:uppercase;">Complete self check-in</a></p>
                                <p style="margin:0;color:#5e6678;font-size:13px;line-height:1.6;">If the button does not open, copy this link into your browser:<br><a href="{{ $selfCheckInUrl }}" style="color:#1769e8;word-break:break-all;">{{ $selfCheckInUrl }}</a></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
