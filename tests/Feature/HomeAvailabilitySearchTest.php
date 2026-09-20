<?php

namespace Tests\Feature;

use App\Models\Apartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAvailabilitySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_homepage_is_public_without_preview_parameters(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Find your stay.')
            ->assertSee('Check availability');
    }

    public function test_home_room_selector_defaults_to_two_rooms(): void
    {
        $this->get(route('home', ['live' => 1]))
            ->assertOk()
            ->assertSee('1 Person(s), 2 rooms');
    }

    public function test_home_apartments_follow_the_configured_sort_order(): void
    {
        Apartment::create(['name' => 'Third Residence', 'slug' => 'third-residence', 'price' => 300, 'sort_order' => 3]);
        Apartment::create(['name' => 'First Residence', 'slug' => 'first-residence', 'price' => 500, 'sort_order' => 1]);
        Apartment::create(['name' => 'Second Residence', 'slug' => 'second-residence', 'price' => 400, 'sort_order' => 2]);

        $this->get(route('home', ['live' => 1]))
            ->assertOk()
            ->assertSeeInOrder([
                'First Residence',
                'Second Residence',
                'Third Residence',
            ]);
    }

    public function test_home_search_requires_checkin_and_checkout_dates(): void
    {
        $response = $this->from(route('home'))->get(route('apartments.index', ['search' => 1]));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['checkin', 'checkout']);
    }

    public function test_home_search_accepts_a_valid_future_date_range(): void
    {
        $response = $this->get(route('apartments.index', [
            'search' => 1,
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(2)->toDateString(),
            'guests' => 1,
            'rooms' => 1,
        ]));

        $response->assertOk();
    }

    public function test_home_search_rejects_past_or_reversed_dates(): void
    {
        $response = $this->from(route('home'))->get(route('apartments.index', [
            'search' => 1,
            'checkin' => now()->subDay()->toDateString(),
            'checkout' => now()->subDays(2)->toDateString(),
        ]));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['checkin', 'checkout']);
    }
}
