<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\PeakPeriod;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\User;
use App\Services\ApartmentQuoteService;
use App\Services\CloudbedsApiService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CloudbedsPriceOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudbeds.api.key' => 'override-test-key', 'cloudbeds.api.property_id' => 'property-1', 'cloudbeds.api.base_url' => 'https://api.cloudbeds.com/api/v1.3']);
        Cache::put('currency.usd-ngn', 1500);
        Storage::fake('local');
        $this->fakeRates();
    }

    public function test_site_override_is_off_by_default_and_cloudbeds_remains_the_normal_source(): void
    {
        $apartment = $this->apartment();
        $quote = $this->quote($apartment);
        $this->assertFalse($apartment->fresh()->price_override_active);
        $this->assertSame(300.0, $quote['total_usd']);
        $this->assertSame('cloudbeds', $quote['rate_source']);
    }

    public function test_active_site_override_uses_the_site_nightly_rate_and_can_be_switched_off(): void
    {
        $apartment = $this->apartment(['price_override_active' => true]);
        $quote = $this->quote($apartment);
        $this->assertSame(500.0, $quote['total_usd']);
        $this->assertSame('site_override', $quote['rate_source']);
        Http::assertNothingSent();
        $apartment->update(['price_override_active' => false]);
        $this->assertSame(300.0, $this->quote($apartment)['total_usd']);
    }

    public function test_an_active_peak_price_overrides_both_cloudbeds_and_the_general_site_rate(): void
    {
        $apartment = $this->apartment(['price_override_active' => true]);
        $this->peak($apartment);
        $quote = $this->quote($apartment);
        $this->assertSame(1000.0, $quote['total_usd']);
        $this->assertSame('peak_override', $quote['rate_source']);
        $this->assertSame(2, $quote['peak_nights']);
        Http::assertNothingSent();
    }

    public function test_peak_prices_do_not_require_the_general_override_switch_to_be_on(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment);
        $this->assertSame(1000.0, $this->quote($apartment)['total_usd']);
        Http::assertNothingSent();
    }

    public function test_partial_peak_stays_use_exact_cloudbeds_daily_rates_not_the_stay_average(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        $quote = $this->quote($apartment);
        $this->assertSame(700.0, $quote['total_usd']); // Peak 500 + Cloudbeds 200, not 500 + average 150.
        $this->assertSame('mixed_override', $quote['rate_source']);
        $this->assertSame(['peak_override', 'cloudbeds'], $quote['pricing_sources']);
        $this->assertSame(1, $quote['peak_nights']);
        $this->assertSame(200.0, $quote['pricing_breakdown'][1]['nightly_usd']);
    }

    public function test_partial_peaks_fall_back_to_an_active_site_rate_instead_of_cloudbeds(): void
    {
        $apartment = $this->apartment(['price_override_active' => true]);
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        $quote = $this->quote($apartment);
        $this->assertSame(750.0, $quote['total_usd']);
        $this->assertSame(['peak_override', 'site_override'], $quote['pricing_sources']);
        Http::assertNothingSent();
    }

    public function test_inactive_and_out_of_range_peaks_do_not_replace_cloudbeds(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['is_active' => false]);
        $this->peak($apartment, ['start_date' => '2027-04-01', 'end_date' => '2027-04-30']);
        $this->assertSame(300.0, $this->quote($apartment)['total_usd']);
    }

    public function test_peak_end_date_includes_its_last_night_but_checkout_does_not_count(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['start_date' => '2027-03-11', 'end_date' => '2027-03-11']);
        $this->assertSame('cloudbeds', $this->quote($apartment, '2027-03-10', '2027-03-11')['rate_source']);
        $this->assertSame(500.0, $this->quote($apartment, '2027-03-11', '2027-03-12')['total_usd']);
        $this->assertSame('cloudbeds', $this->quote($apartment, '2027-03-12', '2027-03-13')['rate_source']);
    }

    public function test_a_specific_peak_price_does_not_override_another_apartment(): void
    {
        $first = $this->apartment();
        $other = $this->apartment(['name' => 'Other residence', 'slug' => 'other-residence', 'apartment_id' => 'room-1']);
        $this->peak($first);
        $this->assertSame(300.0, $this->quote($other)['total_usd']);
    }

    public function test_overlapping_peaks_choose_the_highest_fixed_price_after_currency_conversion(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['apartment_prices' => [$apartment->id => '450.00']]);
        $this->peak($apartment, ['price_currency' => 'NGN', 'apartment_prices' => [$apartment->id => '750000.00']]);
        $this->assertSame(1000.0, $this->quote($apartment)['total_usd']);
    }

    public function test_fixed_peak_rates_replace_percentage_increases_instead_of_stacking(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['increase_percent' => 100]);
        $this->assertSame(1000.0, $this->quote($apartment)['total_usd']);
    }

    public function test_percentage_peaks_apply_to_the_base_source_on_covered_nights(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10', 'apartment_prices' => [], 'increase_percent' => 20]);
        $this->assertSame(320.0, $this->quote($apartment)['total_usd']); // 100 * 1.2 + 200.
        $apartment->update(['price_override_active' => true]);
        $this->assertSame(550.0, $this->quote($apartment)['total_usd']); // 250 * 1.2 + 250.
    }

    public function test_local_discounts_apply_once_after_the_peak_and_site_override_resolution(): void
    {
        $apartment = $this->apartment(['price_override_active' => true, 'sale_price' => 1]);
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        Promotion::create(['name' => 'Local sale', 'scope' => 'global', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true]);
        $quote = $this->quote($apartment);
        $this->assertSame(750.0, $quote['original_total_usd']);
        $this->assertSame(675.0, $quote['total_usd']);
    }

    public function test_ngn_peak_rates_convert_consistently_for_usd_and_ngn_visitors(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['price_currency' => 'NGN', 'apartment_prices' => [$apartment->id => '300000.00']]);
        $this->assertSame(400.0, $this->quote($apartment)['total_usd']);
        $this->assertSame(600000.0, $this->quote($apartment, currency: ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500])['total']);
    }

    public function test_cloudbeds_guest_supplements_are_preserved_for_non_overridden_nights(): void
    {
        $this->fakeRates(['2027-03-10' => 120, '2027-03-11' => 240], baseRates: ['2027-03-10' => 100, '2027-03-11' => 200]);
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        $this->assertSame(740.0, $this->quote($apartment, guests: 3)['total_usd']);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'getRatePlans') && (int) $request['adults'] === 3);
    }

    public function test_ngn_cloudbeds_and_usd_peak_nights_can_be_combined_without_currency_confusion(): void
    {
        $this->fakeRates(['2027-03-10' => 150000, '2027-03-11' => 300000], 'NGN');
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        $this->assertSame(700.0, $this->quote($apartment)['total_usd']);
    }

    #[DataProvider('invalidDailyRateRecords')]
    public function test_missing_or_inconsistent_daily_rates_fail_closed_for_a_mixed_price_stay(array $daily, float $total, ?float $base): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*getHotels*' => Http::response(['success' => true, 'data' => [['propertyID' => 'property-1', 'propertyCurrency' => ['currencyCode' => 'USD']]]]),
            '*getRatePlans*' => Http::response(['success' => true, 'data' => [['roomTypeID' => 'room-1', 'roomTypeName' => 'Beaumont', 'roomRate' => $base, 'totalRate' => $total, 'roomRateDetailed' => $daily]]]),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('daily rates could not be confirmed');
        $this->quote($apartment);
    }

    public static function invalidDailyRateRecords(): array
    {
        $first = ['date' => '2027-03-10', 'totalRate' => 100];
        $second = ['date' => '2027-03-11', 'totalRate' => 200];

        return [
            'missing details' => [[], 300, null],
            'daily sum differs from total' => [[$first, $second], 450, 300],
            'duplicate date' => [[$first, $first, $second], 300, 300],
            'supplement totals missing' => [[['date' => '2027-03-10', 'rate' => 100], ['date' => '2027-03-11', 'rate' => 200]], 360, 300],
        ];
    }

    public function test_daily_base_rates_are_accepted_when_cloudbeds_confirms_there_are_no_guest_supplements(): void
    {
        $apartment = $this->apartment();
        $this->peak($apartment, ['end_date' => '2027-03-10']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*getHotels*' => Http::response(['success' => true, 'data' => [['propertyID' => 'property-1', 'propertyCurrency' => ['currencyCode' => 'USD']]]]),
            '*getRatePlans*' => Http::response(['success' => true, 'data' => [[
                'roomTypeID' => 'room-1', 'roomTypeName' => 'Beaumont', 'roomRate' => 300, 'totalRate' => 300,
                'roomRateDetailed' => [['date' => '2027-03-10', 'rate' => 100], ['date' => '2027-03-11', 'rate' => 200]],
            ]]]),
        ]);
        $this->assertSame(700.0, $this->quote($apartment)['total_usd']);
    }

    public function test_display_and_payment_currency_use_one_stable_peak_snapshot_per_request(): void
    {
        $apartment = $this->apartment();
        $period = $this->peak($apartment);
        $service = app(ApartmentQuoteService::class);
        $usd = $service->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), $this->usd());
        $period->update(['apartment_prices' => [$apartment->id => '800.00']]);
        $ngn = $service->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500]);
        $this->assertSame(1000.0, $usd['total_usd']);
        $this->assertSame(1500000.0, $ngn['total']);
        $this->assertSame(1600.0, $this->quote($apartment)['total_usd']); // A new request sees the edit.
    }

    public function test_admin_can_activate_deactivate_and_preserve_an_override_on_other_edits(): void
    {
        $property = Property::create(['name' => 'Maison Be', 'slug' => 'maison-be']);
        $apartment = $this->apartment(['property_id' => $property->id]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = ['room_name' => $apartment->name, 'property_id' => $property->id, 'status' => 'active'];
        $this->put(route('admin.modules.record.update', ['apartments', $apartment->id]), $data + ['room_price' => 300, 'price_override_active' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($apartment->fresh()->price_override_active);
        $this->assertSame('300.00', $apartment->fresh()->price);
        $this->put(route('admin.modules.record.update', ['apartments', $apartment->id]), $data)->assertSessionHasNoErrors();
        $this->assertTrue($apartment->fresh()->price_override_active);
        $this->assertSame('300.00', $apartment->fresh()->price);
        $this->put(route('admin.modules.record.update', ['apartments', $apartment->id]), $data + ['price_override_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($apartment->fresh()->price_override_active);
    }

    public function test_admin_cannot_enable_an_override_without_a_positive_price(): void
    {
        $property = Property::create(['name' => 'Maison Be', 'slug' => 'maison-be']);
        $apartment = $this->apartment(['property_id' => $property->id]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([0, null, -10] as $price) {
            $this->putJson(route('admin.modules.record.update', ['apartments', $apartment->id]), [
                'room_name' => $apartment->name, 'property_id' => $property->id, 'status' => 'active', 'price_override_active' => 1, 'room_price' => $price,
            ])->assertUnprocessable()->assertJsonValidationErrors('room_price');
        }
        $this->assertFalse($apartment->fresh()->price_override_active);
    }

    public function test_checkout_uses_the_peak_override_discount_and_records_its_source(): void
    {
        config(['services.paystack.public_key' => 'pk_test_override']);
        $apartment = $this->apartment();
        $this->peak($apartment);
        Promotion::create(['name' => 'Local sale', 'scope' => 'global', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => 'room-1']);
        });
        $this->postJson(route('reservations.store', $apartment), $this->checkoutData())
            ->assertOk()->assertJsonPath('payment.amount', 135000000)
            ->assertJsonPath('payment.metadata.booking.rate_source', 'peak_override')
            ->assertJsonPath('payment.metadata.booking.pricing_breakdown.0.nightly_usd', 500);
        Http::assertNothingSent();
    }

    public function test_active_price_overrides_do_not_bypass_cloudbeds_availability(): void
    {
        $apartment = $this->apartment(['price_override_active' => true]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(null);
        });
        $this->postJson(route('reservations.store', $apartment), $this->checkoutData())
            ->assertUnprocessable()->assertJsonMissingPath('payment');
        $this->assertDatabaseCount('checkout_identity_documents', 0);
    }

    private function apartment(array $overrides = []): Apartment
    {
        return Apartment::create(array_replace(['name' => 'Beaumont', 'slug' => 'beaumont', 'price' => 250, 'allow' => true, 'max_adults' => 7], $overrides));
    }

    private function peak(Apartment $apartment, array $overrides = []): PeakPeriod
    {
        return PeakPeriod::create(array_replace(['name' => 'Peak rate', 'start_date' => '2027-03-10', 'end_date' => '2027-03-11', 'is_active' => true, 'increase_percent' => 0, 'price_currency' => 'USD', 'apartment_prices' => [$apartment->id => '500.00']], $overrides));
    }

    private function quote(Apartment $apartment, string $from = '2027-03-10', string $to = '2027-03-12', ?array $currency = null, int $guests = 1): array
    {
        return app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse($from), Carbon::parse($to), $currency ?? $this->usd(), $guests);
    }

    private function usd(): array
    {
        return ['code' => 'USD', 'symbol' => '$', 'rate' => 1];
    }

    private function checkoutData(): array
    {
        return ['checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'guest@example.com', 'phone_country' => 'NG', 'phone' => '8012345678', 'identity_document' => UploadedFile::fake()->image('id.jpg')];
    }

    private function fakeRates(array $rates = ['2027-03-10' => 100, '2027-03-11' => 200], string $currency = 'USD', ?array $baseRates = null): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*getHotels*' => Http::response(['success' => true, 'data' => [['propertyID' => 'property-1', 'propertyCurrency' => ['currencyCode' => $currency]]]]),
            '*getRatePlans*' => function ($request) use ($rates, $baseRates) {
                $days = [];
                foreach (CarbonPeriod::create($request['startDate'], '1 day', Carbon::parse($request['endDate'])->subDay()) as $date) {
                    $key = $date->toDateString();
                    $total = (float) ($rates[$key] ?? 100);
                    $days[] = ['date' => $key, 'rate' => (float) ($baseRates[$key] ?? $total), 'totalRate' => $total];
                }

                return Http::response(['success' => true, 'data' => [['roomTypeID' => 'room-1', 'roomTypeName' => 'Beaumont', 'rateID' => 'base-rate', 'roomRate' => array_sum(array_column($days, 'rate')), 'totalRate' => array_sum(array_column($days, 'totalRate')), 'roomRateDetailed' => $days]]]);
            },
        ]);
    }
}
