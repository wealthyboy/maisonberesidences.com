@props([
    'name' => 'circle',
    'class' => 'h-4 w-4',
])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('gauge')
            <path d="M12 14l3-3" /><path d="M3 12a9 9 0 1 1 18 0" /><path d="M5 19h14" /><path d="M7 15h.01" /><path d="M17 15h.01" />
            @break
        @case('layout-dashboard')
            <rect x="3" y="3" width="7" height="8" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="15" width="7" height="6" rx="1.5" />
            @break
        @case('external-link')
            <path d="M14 4h6v6" /><path d="M20 4l-9 9" /><path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5" />
            @break
        @case('chart-line')
            <path d="M4 19V5" /><path d="M4 19h16" /><path d="M7 15l4-4 3 3 5-7" />
            @break
        @case('layers')
            <path d="M12 3l9 5-9 5-9-5 9-5z" /><path d="M3 12l9 5 9-5" /><path d="M3 16l9 5 9-5" />
            @break
        @case('panels')
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 10h18" /><path d="M9 10v10" />
            @break
        @case('user-rounds')
            <circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0" /><circle cx="17" cy="9" r="2.5" /><path d="M15.5 15.5A5 5 0 0 1 21.5 20" />
            @break
        @case('building')
            <rect x="4" y="3" width="16" height="18" rx="2" /><path d="M9 7h.01" /><path d="M15 7h.01" /><path d="M9 11h.01" /><path d="M15 11h.01" /><path d="M9 15h.01" /><path d="M15 15h.01" />
            @break
        @case('home')
            <path d="M3 11l9-8 9 8" /><path d="M5 10v10h14V10" /><path d="M9 20v-6h6v6" />
            @break
        @case('calendar-check')
            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M3 10h18" /><path d="M9 15l2 2 4-4" />
            @break
        @case('clipboard-check')
            <rect x="6" y="4" width="12" height="17" rx="2" /><path d="M9 4a3 3 0 0 1 6 0" /><path d="M9 13l2 2 4-4" />
            @break
        @case('receipt')
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" /><path d="M9 8h6" /><path d="M9 12h6" /><path d="M9 16h4" />
            @break
        @case('ticket')
            <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z" /><path d="M13 5v14" />
            @break
        @case('calendar-days')
            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M3 10h18" /><path d="M8 14h.01" /><path d="M12 14h.01" /><path d="M16 14h.01" /><path d="M8 18h.01" /><path d="M12 18h.01" />
            @break
        @case('shopping-cart')
            <path d="M3 3h2l2.5 12h10l2-8H7" /><circle cx="10" cy="20" r="1" /><circle cx="17" cy="20" r="1" />
            @break
        @case('map-pin')
            <path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11z" /><circle cx="12" cy="10" r="2.5" />
            @break
        @case('repeat')
            <path d="M17 2l4 4-4 4" /><path d="M3 11V9a3 3 0 0 1 3-3h15" /><path d="M7 22l-4-4 4-4" /><path d="M21 13v2a3 3 0 0 1-3 3H3" />
            @break
        @case('sliders')
            <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" /><circle cx="8" cy="6" r="2" /><circle cx="14" cy="12" r="2" /><circle cx="10" cy="18" r="2" />
            @break
        @case('sparkles')
            <path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z" /><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z" /><path d="M5 14l.7 1.8L8 16.5l-2.3.7L5 19l-.7-1.8L2 16.5l2.3-.7L5 14z" />
            @break
        @case('folders')
            <path d="M3 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z" /><path d="M3 10h18" />
            @break
        @case('map')
            <path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z" /><path d="M9 3v15" /><path d="M15 6v15" />
            @break
        @case('coins')
            <circle cx="8" cy="8" r="4" /><path d="M12 8c0 2.2-1.8 4-4 4" /><path d="M16 8c2.8.4 5 2.2 5 4.5 0 2.5-2.7 4.5-6 4.5-2.2 0-4.2-.9-5.2-2.2" /><path d="M16 17c2.8-.4 5-2.2 5-4.5" />
            @break
        @case('file-text')
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6" /><path d="M8 13h8" /><path d="M8 17h6" />
            @break
        @case('image')
            <rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="8" cy="10" r="1.5" /><path d="M21 16l-5-5-8 8" />
            @break
        @case('film')
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 4v16" /><path d="M17 4v16" /><path d="M3 9h4" /><path d="M17 9h4" /><path d="M3 15h4" /><path d="M17 15h4" />
            @break
        @case('images')
            <rect x="6" y="4" width="15" height="13" rx="2" /><path d="M3 8v10a2 2 0 0 0 2 2h12" /><circle cx="11" cy="9" r="1.5" /><path d="M21 14l-4-4-6 7" />
            @break
        @case('newspaper')
            <path d="M4 5h14a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5z" /><path d="M8 8h6" /><path d="M8 12h8" /><path d="M8 16h5" />
            @break
        @case('star')
            <path d="M12 3l2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9L12 3z" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" /><circle cx="9.5" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9" /><path d="M16 3.1a4 4 0 0 1 0 7.8" />
            @break
        @case('shield-user')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><circle cx="12" cy="9" r="2.2" /><path d="M8.5 15a4 4 0 0 1 7 0" />
            @break
        @case('key')
            <circle cx="8" cy="15" r="4" /><path d="M11 12l8-8" /><path d="M16 5l3 3" /><path d="M14 7l3 3" />
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M3 12h18" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3.5-.2-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V22h-4v-.3a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.2.1-2-3.5.1-.1A1.7 1.7 0 0 0 4.6 15 1.7 1.7 0 0 0 3 14H3v-4h.3a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2-3.5.2.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V2h4v.3a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.2-.1 2 3.5-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.3v4h-.3a1.7 1.7 0 0 0-1.3 1.1z" />
            @break
        @case('wallet')
            <path d="M4 7h15a2 2 0 0 1 2 2v10H5a2 2 0 0 1-2-2V7z" /><path d="M4 7l12-4 1 4" /><path d="M16 13h5" />
            @break
        @case('list-checks')
            <path d="M9 6h11" /><path d="M9 12h11" /><path d="M9 18h11" /><path d="M4 6l1 1 2-2" /><path d="M4 12l1 1 2-2" /><path d="M4 18l1 1 2-2" />
            @break
        @case('ban')
            <circle cx="12" cy="12" r="9" /><path d="M5.7 5.7l12.6 12.6" />
            @break
        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
