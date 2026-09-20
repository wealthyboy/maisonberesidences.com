<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_menu_shows_logout_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home', ['live' => 1]))
            ->assertOk()
            ->assertSee('Logout')
            ->assertDontSee('>Login<', false);
    }

    public function test_authenticated_user_can_log_out_from_the_frontend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
