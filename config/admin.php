<?php

return [
    'modules' => [
        [
            'section' => 'Properties',
            'icon' => 'home',
            'items' => [
                ['slug' => 'apartments', 'label' => 'Apartments', 'icon' => 'building', 'description' => 'Manage apartment records, media, pricing, attributes, and availability.'],
                ['slug' => 'properties', 'label' => 'Properties', 'icon' => 'home', 'description' => 'Manage property-level inventory.'],
                ['slug' => 'invoices', 'label' => 'Invoices', 'icon' => 'receipt', 'description' => 'Create invoices, receipts, reports, downloads, and email sends.'],
            ],
        ],
        [
            'section' => 'Sales',
            'icon' => 'chart-line',
            'items' => [
                ['slug' => 'reservations', 'label' => 'Reservations', 'icon' => 'calendar-check', 'description' => 'Create, edit, review, and resend reservation details.'],
                ['slug' => 'vouchers', 'label' => 'Vouchers', 'icon' => 'ticket', 'description' => 'Create discount vouchers, usage limits, and campaign rules.'],
                ['slug' => 'peak-periods', 'label' => 'Peak Periods', 'icon' => 'calendar-days', 'description' => 'Configure seasonal pricing windows and stay limits.'],
                ['slug' => 'abandoned-carts', 'label' => 'Abandoned Carts', 'icon' => 'shopping-cart', 'description' => 'Review abandoned checkout records and follow-up status.'],
            ],
        ],
        [
            'section' => 'Catalog',
            'icon' => 'layers',
            'items' => [
                ['slug' => 'attributes', 'label' => 'Attributes', 'icon' => 'sliders', 'description' => 'Manage amenities, icons, filters, import data, and sort order.'],
                ['slug' => 'facilities', 'label' => 'Facilities', 'icon' => 'sparkles', 'description' => 'Maintain facility records used by apartments and properties.'],
                ['slug' => 'locations', 'label' => 'Locations', 'icon' => 'map', 'description' => 'Manage locations, hierarchy, images, and search destinations.'],
            ],
        ],
        [
            'section' => 'Content',
            'icon' => 'panels',
            'items' => [
                ['slug' => 'pages', 'label' => 'Pages', 'icon' => 'file-text', 'description' => 'Edit site information pages such as about, contact, gallery, and experiences.'],
                ['slug' => 'banners', 'label' => 'Banners', 'icon' => 'image', 'description' => 'Manage homepage and promotional banner assets.'],
                ['slug' => 'reviews', 'label' => 'Reviews', 'icon' => 'star', 'description' => 'Moderate customer reviews and testimonials.'],
            ],
        ],
        [
            'section' => 'People',
            'icon' => 'user-rounds',
            'items' => [
                ['slug' => 'customers', 'label' => 'Customers', 'icon' => 'users', 'description' => 'View customer accounts, bookings, and contact records.'],
                ['slug' => 'users', 'label' => 'Admin Users', 'icon' => 'shield-user', 'description' => 'Manage team members and admin access.'],
                ['slug' => 'permissions', 'label' => 'Permissions', 'icon' => 'key', 'description' => 'Maintain admin permission records.'],
            ],
        ],
        [
            'section' => 'Settings',
            'icon' => 'settings',
            'items' => [
                ['slug' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'description' => 'Manage system settings, contact details, payment rules, and brand defaults.'],
            ],
        ],
    ],
];
