<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Maison Beresidences offers refined short-stay residences and premium apartment experiences in Lagos.">
        <title>Maison Beresidences</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="construction-page-body">
        <main class="construction-page">
            <section class="construction-copy">
                <a class="construction-brand" href="{{ url('/') }}" aria-label="Maison Beresidences home">
                    <span class="construction-brand-mark"><span>M</span><span>B</span></span>
                    <span class="construction-brand-name">Maison Be <small>Residences</small></span>
                </a>

                <div class="construction-content">
                    <p class="construction-eyebrow">Launching soon</p>
                    <h1>Contemporary luxury residences in Lagos.</h1>
                    <p class="construction-intro">A refined short-stay apartment experience shaped around privacy, comfort, and considered hospitality.</p>
                    <div class="construction-contact">
                        <p>Reservations</p>
                        <a href="mailto:reservations@maisonberesidences.com">reservations@maisonberesidences.com</a>
                    </div>
                </div>

                <footer class="construction-footer">
                    <span>Maison Be Residences</span>
                    <span>Lagos, Nigeria</span>
                </footer>
            </section>

            <section class="construction-visual" aria-hidden="true">
                <div class="construction-monogram"><span>M</span><span>B</span></div>
                <div class="construction-visual-label">
                    <span>Private stays</span>
                    <p>Comfort, privacy and calm.</p>
                </div>
            </section>
        </main>
    </body>
</html>
