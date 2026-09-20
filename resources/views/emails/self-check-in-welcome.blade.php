<!DOCTYPE html>
<html lang="en">
    <body style="margin:0;padding:30px;background:#f8f4ec;color:#06112e;font-family:Arial,sans-serif;">
        <div style="max-width:640px;margin:auto;padding:30px;background:#fff;border:1px solid #d8cba9;">
            <img src="{{ asset('brand/maison-be-logo-official.png') }}" alt="Maison Be Residences" width="150">
            <h1 style="margin:25px 0 12px;">Welcome to Maison Be.</h1>
            <p>Hello {{ $details['first_name'] }},</p>
            <p>Your self check-in for <strong>{{ $details['apartment'] }}</strong> has been received successfully. We look forward to welcoming you on {{ optional($details['checkin'])->format('l, j F Y') }}.</p>
            <p>If you need assistance before arrival, contact <a href="mailto:reservations@maisonberesidences.com">reservations@maisonberesidences.com</a> or WhatsApp <a href="https://wa.me/2349065007079">+234 906 500 7079</a>.</p>
            <p>Warm regards,<br><strong>Maison Be Residences</strong></p>
        </div>
    </body>
</html>
