<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\PeakPeriod;
use App\Models\User;
use App\Services\ApartmentQuoteService;
use App\Services\CloudbedsApiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PeakPeriodPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('currency.usd-ngn', 1500);
        Http::fake(['*' => Http::response(['rates' => ['NGN' => 1500]])]);
    }

    public function test_admin_can_save_different_apartment_prices_under_one_date_range(): void
    {
        $first = $this->apartment('Berkeley');
        $second = $this->apartment('Stanmore');
        $blank = $this->apartment('Beaufort');
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.modules.store', 'peak-periods'), $this->data([
                'price_currency' => 'NGN',
                'apartment_prices' => [$first->id => 300000, $second->id => 450000, $blank->id => ''],
            ]))->assertSessionHasNoErrors();

        $period = PeakPeriod::query()->sole();
        $this->assertSame('2027-03-10', $period->start_date->toDateString());
        $this->assertSame('2027-03-12', $period->end_date->toDateString());
        $this->assertSame('NGN', $period->price_currency);
        $this->assertSame('0.00', $period->increase_percent);
        $this->assertSame([$first->id => '300000.00', $second->id => '450000.00'], $period->apartment_prices);
    }

    public function test_admin_form_lists_each_apartment_and_restores_saved_prices(): void
    {
        $first = $this->apartment('Berkeley');
        $second = $this->apartment('Stanmore');
        $period = $this->period(['apartment_prices' => [$first->id => '200.00', $second->id => '250.00']]);
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.modules.record.edit', ['peak-periods', $period->id]))
            ->assertOk()->assertSeeInOrder(['Start date', 'End date', 'Apartment prices for this date range'])
            ->assertSee('name="apartment_prices['.$first->id.']" value="200.00"', false)
            ->assertSee('name="apartment_prices['.$second->id.']" value="250.00"', false);
        $this->get(route('admin.modules.record.show', ['peak-periods', $period->id]))
            ->assertOk()->assertSee('Apartment-specific nightly prices')->assertSee('USD 200.00 / night');
    }

    public function test_admin_can_update_one_price_and_clear_another(): void
    {
        $first = $this->apartment('Berkeley');
        $second = $this->apartment('Stanmore');
        $period = $this->period(['apartment_prices' => [$first->id => '200.00', $second->id => '250.00']]);
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->put(route('admin.modules.record.update', ['peak-periods', $period->id]), $this->data([
                'discount' => 10, 'apartment_prices' => [$first->id => 275.50, $second->id => ''],
            ]))->assertSessionHasNoErrors();
        $this->assertSame([$first->id => '275.50'], $period->fresh()->apartment_prices);
        $this->assertSame('10.00', $period->fresh()->increase_percent);
    }

    public function test_legacy_percentage_only_periods_can_still_be_created(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.modules.store', 'peak-periods'), $this->data(['discount' => 25]))
            ->assertSessionHasNoErrors();
        $this->assertSame('25.00', PeakPeriod::query()->sole()->increase_percent);
        $this->assertSame([], PeakPeriod::query()->sole()->apartment_prices);
    }

    public function test_invalid_prices_currencies_ids_and_date_ranges_are_rejected(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([
            ['apartment_prices' => [$apartment->id => -1]],
            ['apartment_prices' => [$apartment->id => 0]],
            ['apartment_prices' => [$apartment->id => '12.345']],
            ['apartment_prices' => [$apartment->id => 'not-a-price']],
            ['apartment_prices' => [999999 => 200]],
            ['apartment_prices' => [$apartment->id => 200], 'price_currency' => 'INVALID'],
            ['apartment_prices' => [$apartment->id => 200], 'end_date' => '2027-03-09'],
            ['apartment_prices' => [$apartment->id => '']],
        ] as $invalid) {
            $this->postJson(route('admin.modules.store', 'peak-periods'), $this->data($invalid))
                ->assertUnprocessable();
        }
        $this->assertDatabaseCount('peak_periods', 0);
    }

    public function test_specific_prices_only_affect_their_own_apartment(): void
    {
        $first = $this->apartment('Berkeley');
        $second = $this->apartment('Stanmore');
        $third = $this->apartment('Beaufort');
        $this->period(['apartment_prices' => [$first->id => '200.00', $second->id => '250.00']]);
        $this->assertSame(400.0, $this->quote($first)['total_usd']);
        $this->assertSame(500.0, $this->quote($second)['total_usd']);
        $this->assertSame(200.0, $this->quote($third)['total_usd']);
        $this->assertSame(200.0, $this->quote($first)['nightly']);
    }

    public function test_fixed_rate_replaces_the_percentage_instead_of_stacking(): void
    {
        $apartment = $this->apartment('Berkeley');
        $other = $this->apartment('Stanmore');
        $this->period(['increase_percent' => 50, 'discount' => 50, 'apartment_prices' => [$apartment->id => '120.00']]);
        $this->assertSame(240.0, $this->quote($apartment)['total_usd']);
        $this->assertSame(300.0, $this->quote($other)['total_usd']);
    }

    public function test_an_explicit_rate_can_be_lower_than_the_normal_rate(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '80.00']]);
        $this->assertSame(160.0, $this->quote($apartment)['total_usd']);
    }

    public function test_only_peak_nights_are_charged_at_the_specific_rate(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '200.00']]);
        $quote = $this->quote($apartment, '2027-03-09', '2027-03-14');
        $this->assertSame(800.0, $quote['total_usd']);
        $this->assertSame(160.0, $quote['nightly']);
        $this->assertSame(3, $quote['peak_nights']);
    }

    public function test_end_date_is_inclusive_but_checkout_is_not_a_billable_night(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '200.00']]);
        $this->assertSame(200.0, $this->quote($apartment, '2027-03-12', '2027-03-13')['total_usd']);
        $this->assertSame(100.0, $this->quote($apartment, '2027-03-09', '2027-03-10')['total_usd']);
    }

    public function test_inactive_and_out_of_range_periods_do_not_change_prices(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['is_active' => false, 'apartment_prices' => [$apartment->id => '200.00']]);
        $this->period(['start_date' => '2027-04-01', 'end_date' => '2027-04-03', 'apartment_prices' => [$apartment->id => '250.00']]);
        $this->assertSame(200.0, $this->quote($apartment)['total_usd']);
    }

    public function test_overlapping_periods_use_the_highest_specific_price_once(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '200.00']]);
        $this->period(['apartment_prices' => [$apartment->id => '250.00']]);
        $this->period(['increase_percent' => 200, 'discount' => 200]);
        $this->assertSame(500.0, $this->quote($apartment)['total_usd']);
    }

    public function test_ngn_peak_prices_convert_for_both_ngn_and_usd_visitors(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['price_currency' => 'NGN', 'apartment_prices' => [$apartment->id => '300000.00']]);
        $this->assertSame(400.0, $this->quote($apartment)['total_usd']);
        $quote = app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), [
            'code' => 'NGN', 'symbol' => '₦', 'rate' => 1500,
        ]);
        $this->assertSame(600000.0, $quote['total']);
        $this->assertSame(300000.0, $quote['nightly']);
    }

    public function test_usd_peak_prices_are_converted_for_ngn_visitors(): void
    {
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '200.00']]);
        $quote = app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), [
            'code' => 'NGN', 'symbol' => '₦', 'rate' => 1500,
        ]);
        $this->assertSame(600000.0, $quote['total']);
    }

    public function test_checkout_uses_the_server_calculated_peak_price(): void
    {
        config(['services.paystack.public_key' => 'pk_test_peak_pricing']);
        $this->mock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => 'test-room-type']);
        });
        $apartment = $this->apartment('Berkeley');
        $this->period(['apartment_prices' => [$apartment->id => '200.00']]);
        $this->postJson(route('reservations.store', $apartment), [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12',
            'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'guest@example.com',
            'country_code' => '+234', 'phone_country' => 'NG', 'phone' => '8012345678', 'country' => 'Nigeria',
        ])->assertOk()
            ->assertJsonPath('payment.metadata.booking.accommodation_subtotal', 600000)
            ->assertJsonPath('payment.amount', 60000000);
    }

    private function apartment(string $name): Apartment
    {
        return Apartment::create(['name' => $name, 'slug' => str($name)->slug(), 'price' => 100, 'allow' => true, 'max_adults' => 4]);
    }

    private function data(array $overrides = []): array
    {
        return array_replace([
            'name' => 'March peak', 'start_date' => '2027-03-10', 'end_date' => '2027-03-12',
            'discount' => '', 'is_active' => 1, 'price_currency' => 'USD',
        ], $overrides);
    }

    private function period(array $overrides = []): PeakPeriod
    {
        return PeakPeriod::create(array_replace([
            'name' => 'March peak', 'start_date' => '2027-03-10', 'end_date' => '2027-03-12',
            'increase_percent' => 0, 'discount' => 0, 'is_active' => true,
            'price_currency' => 'USD', 'apartment_prices' => [],
        ], $overrides));
    }

    private function quote(Apartment $apartment, string $checkin = '2027-03-10', string $checkout = '2027-03-12'): array
    {
        return app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse($checkin), Carbon::parse($checkout), [
            'code' => 'USD', 'symbol' => '$', 'rate' => 1,
        ]);
    }
}
