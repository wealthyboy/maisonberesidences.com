@php
    $roomName = trim((string) ($roomType['name'] ?? 'Maison Be Residence'));
    $maxGuests = (int) ($roomType['max_guests'] ?? 0);
@endphp

<article class="residence-card" data-apartment-card data-cloudbeds-room-type="{{ $roomType['id'] ?? '' }}">
    <div class="residence-gallery">
        <div class="residence-gallery-slide is-active" style="--slide-image: url('{{ asset('media/maisonbe-hero-source.jpg') }}');">
            <img src="{{ asset('media/maisonbe-hero-source.jpg') }}" alt="{{ $roomName }} at Maison Be" loading="lazy" decoding="async">
        </div>
    </div>
    <div class="residence-card-copy">
        <p>Maison Be Residences</p>
        <h3>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($roomName)) }}</h3>

        @if ($maxGuests > 0)
            <ul class="residence-card-highlights">
                <li><x-amenity-icon name="guests" />Sleeps {{ $maxGuests }}</li>
            </ul>
        @endif

        <div class="residence-card-footer">
            <a class="residence-card-book" href="#apartment-availability">Check availability</a>
        </div>
    </div>
</article>
