<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Maison Be Residences offers refined short-stay residences and premium apartment experiences in Lagos.">
        <title>Maison Be Residences</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="construction-page-body">
        <main class="construction-page">
            <section class="construction-copy">
                <a class="construction-brand" href="{{ url('/') }}" aria-label="Maison Be Residences home"><x-brand-logo /></a>

                <div class="construction-content">
                    <p class="construction-eyebrow">Launching soon</p>
                    <h1>A contemporary boutique apart-hotel in the heart of Ikoyi, Lagos.</h1>
                    <p class="construction-intro">Thoughtfully designed for exceptional comfort, complete privacy and warm, impeccable hospitality.</p>
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
                <img class="construction-monogram" src="{{ asset('brand/maison-be-mark-official.png') }}" alt="">
                <div class="construction-visual-label">
                    <p>Live Beautifully</p>
                </div>
            </section>
        </main>
    </body>
</html>
