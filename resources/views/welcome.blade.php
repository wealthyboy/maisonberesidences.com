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
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="section-pad">
            <div class="container">
                <div class="row min-vh-100 align-items-center">
                    <div class="col-lg-8">
                        <span class="eyebrow text-dark">Maison Beresidences</span>
                        <h1 class="section-title mt-3">Contemporary luxury residences in Lagos.</h1>
                        <p class="section-copy">A refined short-stay apartment experience shaped around privacy, comfort, and considered hospitality.</p>
                        <a class="btn btn-dark rounded-0 px-4 py-3" href="{{ url('/?live=true') }}">Enter Site</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
