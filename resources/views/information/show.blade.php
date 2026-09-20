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
        <x-site-page-header />
        <main class="information-page">
            <p class="eyebrow">Maison Be Residences</p>
            <h1>{{ $information->title }}</h1>
            @php
                $pageContent = html_entity_decode(
                    $information->description ?: $information->teaser ?: 'Information will be available shortly.',
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
                $pageContent = strip_tags($pageContent, '<p><br><strong><em><b><i><u><ul><ol><li><h2><h3><h4><h5><h6><blockquote><hr>');
                $pageContent = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $pageContent);
            @endphp
            <div class="information-page-content">{!! $pageContent !!}</div>
        </main>
        <x-site-footer />
    </body>
</html>
