<?php

namespace Database\Seeders;

use App\Models\Attribute as ApartmentAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApartmentAttributeSeeder extends Seeder
{
    private const TYPE = 'apartment_facility';

    private const ICONS = [
        'Additional toilet' => 'toilet',
        'Bidet' => 'toilet',
        'Hairdryer' => 'hairdryer',
        'Private bathroom' => 'bathroom',
        'Toilet paper' => 'toilet',
        'Towels' => 'towel',
        'Towels/sheets (extra fee)' => 'towel',
        'Bed sheets' => 'bed',
        'Blackout drapes/curtains' => 'curtains',
        'Climate-controlled air conditioning' => 'air-conditioning',
        'Linen' => 'bed',
        'Wardrobe or closet' => 'wardrobe',
        'All pools are free of charge' => 'pool',
        'Dining area' => 'dining',
        'Sofa' => 'sofa',
        'Cable tv' => 'tv',
        'Cinema' => 'cinema',
        'Flat-screen TV' => 'tv',
        'Speakers' => 'speaker',
        'Free WiFi' => 'wifi',
        'Hot tub/Jacuzzi' => 'hot-tub',
        'Pool/beach towels' => 'towel',
        'Sun loungers or beach chairs' => 'sun-lounger',
        'Blender' => 'blender',
        'Cleaning products' => 'cleaning',
        'Dining table' => 'dining',
        'Electric kettle' => 'kettle',
        'Kitchenware' => 'kitchen',
        'Microwave' => 'microwave',
        'Oven' => 'oven',
        'Toaster' => 'toaster',
        'Tumble dryer' => 'dryer',
        'Washing machine' => 'washer',
        '24-hour room service' => 'room-service',
        'Daily housekeeping' => 'housekeeping',
        'Fresh bed sheets (on request)' => 'bed',
        'Fresh towels' => 'towel',
        'Parking included' => 'parking',
        'Workspace' => 'workspace',
        'Elevator' => 'elevator',
        'Stairs (No Elevator)' => 'stairs',
    ];

    public function run(): void
    {
        $groups = [
            'Bathroom' => [
                'Additional toilet',
                'Bidet',
                'Hairdryer',
                'Private bathroom',
                'Toilet paper',
                'Towels',
                'Towels/sheets (extra fee)',
            ],
            'Bedroom' => [
                'Bed sheets',
                'Blackout drapes/curtains',
                'Climate-controlled air conditioning',
                'Linen',
                'Wardrobe or closet',
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
                'Speakers',
            ],
            'Internet' => [
                'Free WiFi',
            ],
            'Wellness' => [
                'Hot tub/Jacuzzi',
                'Pool/beach towels',
                'Sun loungers or beach chairs',
            ],
            'Kitchen & Dining' => [
                'Blender',
                'Cleaning products',
                'Dining table',
                'Electric kettle',
                'Kitchenware',
                'Microwave',
                'Oven',
                'Toaster',
                'Tumble dryer',
                'Washing machine',
            ],
            'Food and drink' => [
                '24-hour room service',
            ],
            'More' => [
                'Daily housekeeping',
                'Fresh bed sheets (on request)',
                'Fresh towels',
                'Parking included',
                'Workspace',
            ],
            'Accessibility' => [
                'Elevator',
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
                ApartmentAttribute::updateOrCreate(
                    ['slug' => $group->slug . '-' . Str::slug($itemName)],
                    [
                        'parent_id' => $group->id,
                        'name' => $itemName,
                        'icon' => self::ICONS[$itemName] ?? 'check',
                        'type' => self::TYPE,
                        'sort_order' => $itemOrder + 1,
                        'is_active' => true,
                    ],
                );
            }

            $groupOrder++;
        }
    }
}
