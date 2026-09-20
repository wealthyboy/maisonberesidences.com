@php
    $logoPath = public_path('brand/maison-be-logo-official.png');
    $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 34px; }
            body { margin: 0; color: #06112e; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            .header { padding-bottom: 18px; border-bottom: 2px solid #a78135; }
            .logo { width: 145px; }
            h1 { margin: 24px 0 5px; font-family: DejaVu Serif, serif; font-size: 28px; font-weight: normal; }
            .reference { color: #697086; }
            .details { width: 100%; margin-top: 22px; border-collapse: collapse; }
            .details td { width: 50%; padding: 12px; border: 1px solid #ddd3b8; vertical-align: top; }
            .details span { display: block; margin-bottom: 5px; color: #a78135; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
            .id-title { margin: 25px 0 10px; color: #a78135; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; }
            .id-preview { max-width: 100%; max-height: 390px; border: 1px solid #ddd3b8; }
            .notice { padding: 16px; border: 1px solid #ddd3b8; background: #faf7ef; }
            .footer { position: fixed; right: 0; bottom: 0; left: 0; color: #697086; font-size: 9px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">
            @if ($logo)<img class="logo" src="{{ $logo }}" alt="Maison Be Residences">@endif
            <h1>Guest Self Check-in</h1>
            <div class="reference">Reservation {{ $details['reservation'] }} · Submitted {{ now()->format('j M Y, g:i A') }}</div>
        </div>

        <table class="details">
            <tr><td><span>First name</span>{{ $details['first_name'] }}</td><td><span>Last name</span>{{ $details['last_name'] }}</td></tr>
            <tr><td><span>Email</span>{{ $details['email'] }}</td><td><span>Phone</span>{{ $details['phone'] }}</td></tr>
            <tr><td><span>Check-in</span>{{ optional($details['checkin'])->format('l, j F Y') }}</td><td><span>Check-out</span>{{ optional($details['checkout'])->format('l, j F Y') }}</td></tr>
            <tr><td colspan="2"><span>Apartment</span>{{ $details['apartment'] }}</td></tr>
        </table>

        <p class="id-title">Identity document</p>
        @if ($idPreview)
            <img class="id-preview" src="{{ $idPreview }}" alt="Guest identity document">
        @else
            <div class="notice">The guest supplied a PDF identity document. It is attached separately to the reservations email.</div>
        @endif

        <div class="footer">Maison Be Residences · Private guest check-in record</div>
    </body>
</html>
