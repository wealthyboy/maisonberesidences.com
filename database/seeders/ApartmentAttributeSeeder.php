<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Attribute as ApartmentAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApartmentAttributeSeeder extends Seeder
{
    private const TYPE = 'apartment_facility';

    private const ICONS = [
        'Bathroom' => 'bathroom',
        'Additional toilet' => 'toilet',
        'Bidet' => 'toilet',
        'Body lotion' => 'bathroom',
        'Conditioner' => 'bathroom',
        'Hairdryer' => 'hairdryer',
        'Hot water' => 'bathroom',
        'Private bathroom' => 'bathroom',
        'Shampoo' => 'bathroom',
        'Shower' => 'bathroom',
        'Shower gel' => 'bathroom',
        'Toilet paper' => 'toilet',
        'Towels' => 'towel',
        'Towels/sheets (extra fee)' => 'towel',
        'Bed sheets' => 'bed',
        'Blackout curtains' => 'curtains',
        'Climate-controlled air conditioning' => 'air-conditioning',
        'Fresh bed sheets (upon request)' => 'bed',
        'Fresh towels' => 'towel',
        'Full size mirror' => 'wardrobe',
        'Iron' => 'check',
        'Ironing board' => 'check',
        'Linen' => 'bed',
        'Non-smoking' => 'check',
        'Wardrobe or closet' => 'wardrobe',
        'All pools are free of charge' => 'pool',
        'Dining area' => 'dining',
        'Sofa' => 'sofa',
        'Cable tv' => 'tv',
        'Cinema' => 'cinema',
        'Flat-screen TV' => 'tv',
        'In-built smart home audio system' => 'speaker',
        'Ethernet internet connection' => 'wifi',
        'Free WiFi' => 'wifi',
        'Hot tub/Jacuzzi' => 'hot-tub',
        'Pool/beach towels' => 'towel',
        'Sun loungers or beach chairs' => 'sun-lounger',
        'Blender' => 'blender',
        'Air fryer' => 'oven',
        'Cleaning products' => 'cleaning',
        'Dining table' => 'dining',
        'Dishwasher' => 'dishwasher',
        'Electric kettle' => 'kettle',
        'Glassware and cups' => 'dining',
        'In-built refrigerator' => 'kitchen',
        'Kitchenware' => 'kitchen',
        'Microwave' => 'microwave',
        'Oven' => 'oven',
        'Plates and bowls' => 'dining',
        'Stove' => 'oven',
        'Toaster' => 'toaster',
        'Tumble dryer' => 'dryer',
        'Washing machine' => 'washer',
        '24-hour room service' => 'room-service',
        'Air purifiers' => 'air-conditioning',
        'Humidifier' => 'air-conditioning',
        'Towel and linen reuse program' => 'towel',
        'Carbon monoxide detector' => 'check',
        'Fire alarms' => 'check',
        'Fire extinguishers' => 'check',
        'First aid kits' => 'check',
        'Smoke detectors' => 'check',
        'Complimentary bottled water' => 'check',
        'Daily housekeeping' => 'housekeeping',
        'Parking included' => 'parking',
        'Workspace' => 'workspace',
        'Elevator' => 'elevator',
        '2 exclusive elevators with direct penthouse access' => 'elevator',
        'Stairs (No Elevator)' => 'stairs',
    ];

    private const LEGACY_NAMES = [
        'Blackout curtains' => ['Blackout drapes/curtains'],
        'Fresh bed sheets (upon request)' => ['Fresh bed sheets (on request)'],
        'In-built smart home audio system' => ['Speakers'],
    ];

    public function run(): void
    {
        $groups = [
            'Bathroom' => [
                'Bathroom',
                'Additional toilet',
                'Bidet',
                'Body lotion',
                'Conditioner',
                'Hairdryer',
                'Hot water',
                'Private bathroom',
                'Shampoo',
                'Shower',
                'Shower gel',
                'Toilet paper',
                'Towels',
                'Towels/sheets (extra fee)',
            ],
            'Bedroom' => [
                'Bed sheets',
                'Climate-controlled air conditioning',
                'Linen',
                'Wardrobe or closet',
            ],
            'Comfort & Essentials' => [
                'Blackout curtains',
                'Full size mirror',
                'Iron',
                'Ironing board',
                'Fresh towels',
                'Non-smoking',
                'Fresh bed sheets (upon request)',
            ],
            'Outdoors' => [
                'All pools are free of charge',
            ],
            'Living Area' => [
                'Dining area',
                'Sofa',
            ],
            'Entertainment' => [
                'Cable tv',
                'Cinema',
                'Flat-screen TV',
                'In-built smart home audio system',
            ],
            'Internet' => [
                'Ethernet internet connection',
                'Free WiFi',
            ],
            'Wellness' => [
                'Hot tub/Jacuzzi',
                'Pool/beach towels',
                'Sun loungers or beach chairs',
            ],
            'Kitchen & Dining' => [
                'Air fryer',
                'Blender',
                'Cleaning products',
                'Dining table',
                'Dishwasher',
                'Electric kettle',
                'Glassware and cups',
                'In-built refrigerator',
                'Kitchenware',
                'Microwave',
                'Oven',
                'Plates and bowls',
                'Stove',
                'Toaster',
                'Tumble dryer',
                'Washing machine',
            ],
            'Environment & Sustainability' => [
                'Humidifier',
                'Air purifiers',
                'Towel and linen reuse program',
            ],
            'Safety & Security' => [
                'Carbon monoxide detector',
                'Fire alarms',
                'Fire extinguishers',
                'First aid kits',
                'Smoke detectors',
            ],
            'Food and drink' => [
                '24-hour room service',
            ],
            'More' => [
                'Complimentary bottled water',
                'Daily housekeeping',
                'Parking included',
                'Workspace',
            ],
            'Accessibility' => [
                'Elevator',
                '2 exclusive elevators with direct penthouse access',
                'Stairs (No Elevator)',
            ],
        ];

        $groupOrder = 1;

        foreach ($groups as $groupName => $items) {
            $group = ApartmentAttribute::updateOrCreate(
                ['slug' => Str::slug($groupName)],
                [
                    'parent_id' => null,
                    'name' => $groupName,
                    'type' => self::TYPE,
                    'sort_order' => $groupOrder,
                    'is_active' => true,
                ],
            );

            foreach ($items as $itemOrder => $itemName) {
                $item = ApartmentAttribute::query()
                    ->where('type', self::TYPE)
                    ->whereNotNull('parent_id')
                    ->whereIn('name', [$itemName, ...(self::LEGACY_NAMES[$itemName] ?? [])])
                    ->first() ?? new ApartmentAttribute();

                $item->fill([
                    'parent_id' => $group->id,
                    'name' => $itemName,
                    'slug' => $group->slug.'-'.Str::slug($itemName),
                    'icon' => self::ICONS[$itemName] ?? 'check',
                    'type' => self::TYPE,
                    'sort_order' => $itemOrder + 1,
                    'is_active' => true,
                ])->save();
            }

            $groupOrder++;
        }

        $penthouseAttributeIds = ApartmentAttribute::query()
            ->where('type', self::TYPE)
            ->whereIn('name', [
                'Air fryer',
                'In-built smart home audio system',
                'Elevator',
                '2 exclusive elevators with direct penthouse access',
            ])
            ->pluck('id');

        Apartment::query()
            ->where(function ($query): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%penthouse%'])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ['%penthouse%'])
                    ->orWhereRaw('LOWER(type) LIKE ?', ['%penthouse%']);
            })
            ->each(fn (Apartment $apartment) => $apartment->attributes()->syncWithoutDetaching($penthouseAttributeIds));
    }
}
