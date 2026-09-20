<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAvailabilitySearchTest extends TestCase
{
    use RefreshDatabase;

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
