@props(['name' => 'check'])

@php($icon = strtolower((string) $name))

<svg {{ $attributes->merge([
    'viewBox' => '0 0 24 24',
    'aria-hidden' => 'true',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '1.8',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
]) }}>
    @switch($icon)
        @case('parking') <path d="M5 21V3h8a6 6 0 0 1 0 12H9v6M9 7v4h4a2 2 0 0 0 0-4H9Z"></path> @break
        @case('area') <path d="M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6M3 9l6-6M15 3l6 6M21 15l-6 6M9 21l-6-6"></path> @break
        @case('bedrooms') <path d="M4 21V5h7v16M4 11h7M7.5 8h.01M11 9h7a2 2 0 0 1 2 2v10M15 13h.01M15 17h.01"></path> @break
        @case('guests') <circle cx="9" cy="8" r="3"></circle><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5v2"></path> @break
        @case('bathroom') <path d="M4 12h16M6 12v3a6 6 0 0 0 12 0v-3M8 12V7a4 4 0 0 1 8 0v5M4 20h16"></path> @break
        @case('toilet') <path d="M7 3h7v7H7zM5 10h11v2a6 6 0 0 1-6 6H8M8 18v3h8"></path> @break
        @case('hairdryer') <path d="M4 8h8a4 4 0 0 1 0 8H4V8ZM16 10l5-2v8l-5-2M8 16v5h4"></path> @break
        @case('towel') <path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h6"></path> @break
        @case('bed') <path d="M3 20V9M3 14h18v6M7 14V8h5a3 3 0 0 1 3 3v3M7 11h3M21 20V12"></path> @break
        @case('curtains') <path d="M4 3h16M6 3v18M18 3v18M6 5c5 2 5 12 0 14M18 5c-5 2-5 12 0 14"></path> @break
        @case('air-conditioning') <path d="M4 6h16v7H4zM8 17c0 2-2 2-2 4M13 17c0 2-2 2-2 4M18 17c0 2-2 2-2 4M8 10h8"></path> @break
        @case('wardrobe') <path d="M5 3h14v18H5zM12 3v18M9 12h.01M15 12h.01"></path> @break
        @case('pool') <path d="M3 16c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1M3 20c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1M7 14V5a2 2 0 0 1 4 0M7 9h7V5a2 2 0 0 1 4 0"></path> @break
        @case('dining') <path d="M6 3v7M3 3v4a3 3 0 0 0 6 0V3M6 10v11M15 3v18M15 3c4 2 4 8 0 10"></path> @break
        @case('sofa') <path d="M5 12V8a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v4M3 11a2 2 0 0 1 2 2v3h14v-3a2 2 0 1 1 2 0v6H3v-6a2 2 0 0 1 0-2M6 19v2M18 19v2"></path> @break
        @case('tv') <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m9 2 3 3 3-3M8 22h8"></path> @break
        @case('cinema') <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m10 9 5 3-5 3V9Z"></path> @break
        @case('speaker') <path d="M6 4h12v16H6z"></path><circle cx="12" cy="14" r="3"></circle><circle cx="12" cy="8" r="1"></circle> @break
        @case('wifi') <path d="M3 9a14 14 0 0 1 18 0M6 13a9 9 0 0 1 12 0M9.5 17a4 4 0 0 1 5 0M12 21h.01"></path> @break
        @case('hot-tub') <path d="M4 12h16v4a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-4ZM7 12V9M12 12V8M17 12V9M7 5c1-1 1-2 0-3M12 5c1-1 1-2 0-3M17 5c1-1 1-2 0-3"></path> @break
        @case('sun-lounger') <path d="M4 18h16M6 18l2-8 9 5M8 10l2-5 8 8M7 18l-2 3M18 18l2 3"></path> @break
        @case('blender') <path d="M8 3h8l-1 10H9L8 3ZM10 13h4l2 8H8l2-8ZM7 3h10"></path> @break
        @case('cleaning') <path d="m14 3 2 2-8 8-3 1 1-3 8-8ZM12 9l3 3M4 18h16M7 15l-3 6M17 15l3 6"></path> @break
        @case('kettle') <path d="M7 8h9v10a3 3 0 0 1-3 3h-3a3 3 0 0 1-3-3V8ZM16 10h2a3 3 0 0 1 0 6h-2M9 4c1 1 1 2 0 3M13 3c1 1 1 3 0 4"></path> @break
        @case('kitchen') <path d="M4 3h16v18H4zM4 10h16M9 10v11M7 6h.01M13 6h4M12 14h5M12 17h5"></path> @break
        @case('microwave') <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M6 8h9v8H6zM18 8h.01M18 12h.01M18 16h.01"></path> @break
        @case('oven') <rect x="4" y="3" width="16" height="18" rx="1"></rect><path d="M4 8h16M8 6h.01M12 6h.01M16 6h.01M7 12h10v6H7z"></path> @break
        @case('toaster') <path d="M5 9h14l1 10H4L5 9ZM8 9V5h8v4M7 13h10M20 13h2"></path> @break
        @case('dryer') <rect x="5" y="3" width="14" height="18" rx="2"></rect><circle cx="12" cy="13" r="5"></circle><path d="M8 6h.01M11 6h4M9 13c2-2 4 2 6 0"></path> @break
        @case('washer') <rect x="5" y="3" width="14" height="18" rx="2"></rect><circle cx="12" cy="13" r="5"></circle><path d="M8 6h.01M11 6h4M8 13c2 1 3-1 4 0s2 0 4 0"></path> @break
        @case('room-service') <path d="M3 18h18M5 18a7 7 0 0 1 14 0M12 8V5M10 5h4"></path> @break
        @case('housekeeping') <path d="M12 3v18M8 21h8M9 7l-5 9h5M15 7l5 9h-5"></path> @break
        @case('workspace') <path d="M4 4h16v11H4zM2 19h20M9 15v4M15 15v4"></path> @break
        @case('elevator') <rect x="5" y="3" width="14" height="18" rx="1"></rect><path d="M12 3v18M8 8l2-2 2 2M12 16l2 2 2-2"></path> @break
        @case('stairs') <path d="M3 20h5v-4h4v-4h4V8h5"></path> @break
        @case('info') <circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7h.01"></path> @break
        @case('chevron-right') <path d="m9 18 6-6-6-6"></path> @break
        @default <path d="M20 6 9 17l-5-5"></path>
    @endswitch
</svg>
