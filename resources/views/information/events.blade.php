@php
    $gallery = [
        [
            'title' => 'Private Residences',
            'caption' => 'Fully furnished apartments arranged for calm arrivals, quiet mornings, and easy hosting.',
            'image' => asset('media/maisonbe-hero-source.jpg'),
        ],
        [
            'title' => 'Curated Interiors',
            'caption' => 'Warm living rooms, considered textures, and spaces that feel ready before you arrive.',
            'image' => asset('media/maisonbe-introduction-room.png'),
        ],
        [
            'title' => 'Courtyard Moments',
            'caption' => 'Outdoor corners for slow conversations, private gatherings, and a softer Lagos pause.',
            'image' => asset('media/maisonbe-fountain-courtyard.png'),
        ],
        [
            'title' => 'Dining and Hosting',
            'caption' => 'Generous dining, fitted kitchens, and in-residence service for intimate events.',
            'image' => asset('uploads/apartments/a3f67976-5ba7-4256-acb8-17c02af59087.png'),
        ],
        [
            'title' => 'Restful Suites',
            'caption' => 'King-size comfort, premium bedding, blackout drapes, and bedrooms made for proper rest.',
            'image' => asset('uploads/apartments/0ff64c03-05c0-45ae-ad49-e9981e3e0931.png'),
        ],
        [
            'title' => 'Arrival Support',
            'caption' => 'A discreet hospitality experience for short stays, celebrations, and longer city visits.',
            'image' => asset('uploads/apartments/81db6295-a31e-460c-8f33-91e3a98d3f63.png'),
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Amenities and Events | Maison Be</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page events-page-body">
        <header class="results-header">
            <a class="results-wordmark" href="{{ route('home') }}">Maison Be <small>Residences</small></a>
            <a href="{{ route('apartments.index') }}" class="results-back">View apartments</a>
        </header>

        <main class="events-page">
            <section class="events-hero" aria-labelledby="events-title">
                <div>
                    <p class="eyebrow">Amenities and Events</p>
                    <h1 id="events-title">Spaces made for staying well.</h1>
                </div>
                <p>Maison Be brings together refined interiors, practical amenities, and intimate settings for private stays, quiet celebrations, and effortless hosting in Lagos.</p>
            </section>

            <section class="events-gallery" aria-label="Maison Be amenities and events gallery">
                @foreach ($gallery as $item)
                    <figure class="events-gallery-card">
                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }} at Maison Be" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async" onerror="this.onerror=null;this.src='{{ asset('media/maisonbe-hero-source.jpg') }}';">
                        <figcaption>
                            <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2>{{ $item['title'] }}</h2>
                            <p>{{ $item['caption'] }}</p>
                        </figcaption>
                    </figure>
                @endforeach
            </section>

            <section class="events-note">
                <p class="eyebrow">Plan your stay</p>
                <h2>Reserve the residence that fits the moment.</h2>
                <a href="{{ route('apartments.index') }}">View all apartments <span aria-hidden="true">&rarr;</span></a>
            </section>
        </main>

        <x-site-footer />
    </body>
</html>
