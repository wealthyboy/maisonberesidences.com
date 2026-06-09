<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Maison Be Residences is preparing a refined new home for exceptional short-stay experiences in Lagos.">
        <meta name="theme-color" content="#171848">

        <title>Maison Be Residences | Coming Soon</title>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body>
        <main class="construction-page">
            <section class="construction-copy">
                <header class="brand" aria-label="Maison Be Residences">
                    <span class="brand-mark" aria-hidden="true">
                        <span>M</span><span>B</span>
                    </span>
                    <span class="brand-name">Maison Be <small>Residences</small></span>
                </header>

                <div class="construction-content">
                    <p class="eyebrow">Something exceptional is taking shape</p>
                    <h1>Our new home is <em>coming soon.</em></h1>
                    <p class="intro">
                        We are thoughtfully preparing a new digital experience for Maison Be
                        Residences, where refined living meets the ease of feeling at home.
                    </p>

                    <div class="contact-block">
                        <p>For reservations and enquiries</p>
                        <a href="mailto:reservations@maisonberesidences.com">
                            reservations@maisonberesidences.com
                            <span aria-hidden="true">&nearr;</span>
                        </a>
                    </div>
                </div>

                <footer class="construction-footer">
                    <span>Lagos, Nigeria</span>
                    <span>&copy; {{ date('Y') }} Maison Be Residences</span>
                </footer>
            </section>

            <aside class="construction-visual" aria-label="Maison Be Residences">
                <div class="visual-monogram" aria-hidden="true">
                    <span>M</span>
                    <span>B</span>
                </div>
                <div class="visual-label">
                    <span>01</span>
                    <p>Elevated stays.<br>Considered comfort.</p>
                </div>
            </aside>
        </main>
    </body>
</html>
