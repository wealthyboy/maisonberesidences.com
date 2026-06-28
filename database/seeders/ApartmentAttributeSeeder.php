<?php

namespace Database\Seeders;

use App\Models\Attribute as ApartmentAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApartmentAttributeSeeder extends Seeder
{
    private const TYPE = 'apartment_facility';

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
