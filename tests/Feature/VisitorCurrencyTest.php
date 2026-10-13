<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

class VisitorCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_nigerian_visitors_default_to_ngn(): void
    {
        Cache::put('currency.usd-ngn', 1500);

        $this->withHeader('CF-IPCountry', 'NG')
            ->get('/')
            ->assertOk()
            ->assertSessionHas('currency.code', 'NGN')
            ->assertSessionHas('currency_auto_resolved', true);
    }

    public function test_visitors_outside_nigeria_default_to_usd(): void
    {
        $this->withHeader('CF-IPCountry', 'GB')
            ->get('/')
            ->assertOk()
            ->assertSessionHas('currency.code', 'USD')
            ->assertSessionHas('currency_auto_resolved', true);
    }

    public function test_manual_currency_selection_is_preserved(): void
    {
        $this->withHeader('CF-IPCountry', 'NG')
            ->get('/?currency=USD')
            ->assertOk()
            ->assertSessionHas('currency_preference', 'USD')
            ->assertSessionHas('currency.code', 'USD');

        $this->withHeader('CF-IPCountry', 'NG')
            ->get('/')
            ->assertOk()
            ->assertSessionHas('currency.code', 'USD');
    }

    public function test_nigerian_ip_defaults_to_ngn_without_a_country_header(): void
    {
        Cache::put('currency.usd-ngn', 1500);
        Location::shouldReceive('get')->once()->with('102.89.1.1')
            ->andReturn(Position::make(['countryCode' => 'NG', 'countryName' => 'Nigeria']));
        $this->withHeader('CF-Connecting-IP', '102.89.1.1')->get('/')
            ->assertOk()->assertSessionHas('currency.code', 'NGN');
    }

    public function test_non_nigerian_ip_defaults_to_usd_without_a_country_header(): void
    {
        Location::shouldReceive('get')->once()->with('8.8.8.8')
            ->andReturn(Position::make(['countryCode' => 'US', 'countryName' => 'United States']));
        $this->withHeader('CF-Connecting-IP', '8.8.8.8')->get('/')
            ->assertOk()->assertSessionHas('currency.code', 'USD');
    }

    public function test_auto_currency_sessions_use_the_current_rate_not_the_old_session_rate(): void
    {
        Cache::put('currency.usd-ngn', 1600);
        Location::shouldReceive('get')->never();
        $this->withSession([
            'currency_auto_resolved' => true,
            'currency_auto_resolved_ip' => '102.89.1.1',
            'currency' => ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500, 'country' => 'Nigeria'],
        ])->withHeader('CF-Connecting-IP', '102.89.1.1')->get('/')
            ->assertOk()->assertSessionHas('currency.code', 'NGN')->assertSessionHas('currency.rate', 1600.0);
    }

    public function test_changing_country_updates_an_automatic_currency_session(): void
    {
        $this->withSession([
            'currency_auto_resolved' => true,
            'currency' => ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500, 'country' => 'NG'],
        ])->withHeader('CF-IPCountry', 'GB')->get('/')
            ->assertOk()->assertSessionHas('currency.code', 'USD');
    }

    public function test_changing_ip_rechecks_the_automatic_country_choice(): void
    {
        Location::shouldReceive('get')->once()->with('8.8.8.8')
            ->andReturn(Position::make(['countryCode' => 'US', 'countryName' => 'United States']));
        $this->withSession([
            'currency_auto_resolved' => true,
            'currency_auto_resolved_ip' => '102.89.1.1',
            'currency' => ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500, 'country' => 'Nigeria'],
        ])->withHeader('CF-Connecting-IP', '8.8.8.8')->get('/')
            ->assertOk()->assertSessionHas('currency.code', 'USD');
    }
}
