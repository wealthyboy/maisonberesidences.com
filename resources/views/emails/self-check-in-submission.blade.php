<!DOCTYPE html>
<html lang="en">
    <body style="margin:0;padding:30px;background:#f8f4ec;color:#06112e;font-family:Arial,sans-serif;">
        <div style="max-width:640px;margin:auto;padding:30px;background:#fff;border:1px solid #d8cba9;">
            <img src="{{ asset('brand/maison-be-logo-official.png') }}" alt="Maison Be Residences" width="150">
            <h1 style="margin:25px 0 12px;">New self check-in</h1>
            <p>A guest has completed self check-in for reservation <strong>{{ $details['reservation'] }}</strong>.</p>
            <p><strong>Guest:</strong> {{ $details['first_name'] }} {{ $details['last_name'] }}<br>
            <strong>Email:</strong> {{ $details['email'] }}<br>
            <strong>Phone:</strong> {{ $details['phone'] }}<br>
            <strong>Stay:</strong> {{ optional($details['checkin'])->format('j M Y') }} – {{ optional($details['checkout'])->format('j M Y') }}</p>
            <p>The branded check-in PDF and guest identity document are attached.</p>
        </div>
    </body>
</html>
