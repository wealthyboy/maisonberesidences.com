<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $information->title }} | Maison Be</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page">
        <header class="results-header">
            <a class="results-wordmark" href="{{ route('home') }}" aria-label="Maison Be Residences home"><x-brand-logo /></a>
            <a href="{{ route('home') }}" class="results-back">Back to home</a>
        </header>
        <main class="information-page">
            <p class="eyebrow">Maison Be Residences</p>
            <h1>{{ $information->title }}</h1>
            <div class="information-page-content">{!! nl2br(e($information->description ?: $information->teaser ?: 'Information will be available shortly.')) !!}</div>
        </main>
    </body>
</html>
