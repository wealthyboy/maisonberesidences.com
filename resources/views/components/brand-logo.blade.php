@props([
    'tone' => 'dark',
    'showName' => true,
])

<span {{ $attributes->class(['brand-lockup', 'brand-lockup--'.$tone]) }}>
    <img class="brand-lockup-mark" src="{{ asset('brand/maison-be-mark.png') }}" alt="" aria-hidden="true">
    @if ($showName)
        <span class="brand-lockup-copy">
            <strong>Maison Be</strong>
            <small>Residences</small>
        </span>
    @endif
</span>
