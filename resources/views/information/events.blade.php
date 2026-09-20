@php
    $gallery = [
        [
            'title' => 'Maison Be Café',
            'caption' => 'A warm, art-filled setting for breakfast, coffee, conversation, and an unhurried start to the day.',
            'image' => asset('media/Exterior/StellarMedia-1.jpg'),
        ],
        [
            'title' => 'Poolside Hours',
            'caption' => 'An open-air pool and deck designed for quiet afternoons, easy gatherings, and time at your own pace.',
            'image' => asset('media/Exterior/StellarMedia-3.jpg'),
        ],
        [
            'title' => 'Vivez en beauté',
            'caption' => 'Colour, calm, and a little escape in the middle of Lagos.',
            'image' => asset('media/Exterior/StellarMedia-4.jpg'),
        ],
        [
            'title' => 'Gather Around',
            'caption' => 'Flexible café seating makes room for private conversations and relaxed moments together.',
            'image' => asset('media/Exterior/StellarMedia-2.jpg'),
        ],
        [
            'title' => 'A Considered Arrival',
            'caption' => 'A welcoming entrance framed by greenery, with every detail setting the tone for your stay.',
            'image' => asset('media/Exterior/StellarMedia-5.jpg'),
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Amenities and Events | Maison Be</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page events-page-body">
        <x-site-page-header />

        <main class="events-page">
            <section class="events-hero" aria-labelledby="events-title">
                <img src="{{ asset('media/Exterior/maisonbe-listing-exterior.jpg') }}" alt="Maison Be Residences exterior in Lagos" fetchpriority="high">
                <div class="events-hero-shade"></div>
                <div class="events-hero-copy">
                    <p class="eyebrow">Amenities &amp; Events</p>
                    <h1 id="events-title">More room for the moments that matter.</h1>
                    <p>From quiet mornings at the café to afternoons by the pool, Maison Be offers thoughtful spaces to stay, meet, and celebrate.</p>
                    <a href="#events-spaces">Explore the spaces <span aria-hidden="true">&darr;</span></a>
                </div>
                <p class="events-hero-index"><span>Maison Be</span> Lagos, Nigeria</p>
            </section>

            <section class="events-intro" id="events-spaces">
                <p class="eyebrow">Beyond your residence</p>
                <h2>Spaces that make staying feel beautifully complete.</h2>
                <p>Every shared space is designed with the same care as our apartments: comfortable, expressive, and ready for however you choose to spend the day.</p>
            </section>

            <section class="events-gallery" aria-label="Maison Be amenities and event spaces">
                @foreach ($gallery as $item)
                    <figure class="events-gallery-card">
                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }} at Maison Be" loading="lazy" decoding="async">
                        <figcaption>
                            <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / 05</span>
                            <h2>{{ $item['title'] }}</h2>
                            <p>{{ $item['caption'] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </section>

            <section class="events-note">
                <div>
                    <p class="eyebrow">Stay a little longer</p>
                    <h2>Your residence is only the beginning.</h2>
                    <p>Find the apartment that feels like yours, then enjoy everything Maison Be has waiting beyond the door.</p>
                </div>
                <a href="{{ route('apartments.index') }}">Explore apartments <span aria-hidden="true">&rarr;</span></a>
            </section>
        </main>

        <x-site-footer />
    </body>
</html>
