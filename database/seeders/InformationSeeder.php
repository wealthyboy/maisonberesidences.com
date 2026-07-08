<?php

namespace Database\Seeders;

use App\Models\Information;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InformationSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['title' => 'Apartments', 'teaser' => 'Explore Maison Be residence.', 'sort_order' => 10],
            ['title' => 'Amenities', 'teaser' => 'Comfort, privacy, and thoughtful essentials.', 'sort_order' => 20],
            ['title' => 'About Us', 'teaser' => 'Learn more about Maison Be Residence.', 'sort_order' => 30],
            ['title' => 'Contact', 'teaser' => 'Get in touch with Maison Be Residence.', 'sort_order' => 40],
        ])->each(function (array $page): void {
            Information::query()->updateOrCreate(
                ['slug' => Str::slug($page['title'])],
                [
                    'title' => $page['title'],
                    'name' => $page['title'],
                    'custom_link' => null,
                    'teaser' => $page['teaser'],
                    'description' => $page['teaser'],
                    'sort_order' => $page['sort_order'],
                    'blog' => false,
                ]
            );
        });
    }
}
