@php
    $gallery = [
        [
            'title' => 'Dining and Hosting',
            'caption' => 'Generous dining, fitted kitchens, and in-residence service for intimate events.',
            'image' => asset('media/maisonbe-hero-source.jpg'),
        ],
        [
            'title' => 'Restful Suites',
            'caption' => 'King-size comfort, premium bedding, blackout drapes, and bedrooms made for proper rest.',
            'image' => asset('media/maisonbe-introduction-room.png'),
        ],
        [
            'title' => 'Private Arrivals',
            'caption' => 'A discreet hospitality experience for short stays, celebrations, and longer city visits.',
            'image' => asset('media/maisonbe-fountain-courtyard.png'),
        ],
        [
            'title' => 'Curated Living',
            'caption' => 'Warm living rooms, considered textures, and spaces that feel ready before you arrive.',
            'image' => asset('media/maisonbe-hero-source.jpg'),
        ],
        [
            'title' => 'Quiet Comfort',
            'caption' => 'Fully furnished apartments arranged for calm arrivals, quiet mornings, and easy hosting.',
            'image' => asset('media/maisonbe-introduction-room.png'),
        ],
        [
            'title' => 'Maison Be Details',
            'caption' => 'Refined finishes, functional amenities, and thoughtful corners for an effortless stay.',
            'image' => asset('media/maisonbe-hero-source.jpg'),
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
        <header class="results-header">
            <a class="results-wordmark" href="{{ route('home') }}" aria-label="Maison Be Residences home"><x-brand-logo /></a>
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
