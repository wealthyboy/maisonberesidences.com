<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VisitorCurrencyTest extends TestCase
{
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
}
