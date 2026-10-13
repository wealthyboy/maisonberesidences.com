<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Attribute;
use App\Models\PeakPeriod;
use App\Models\Promotion;
use App\Services\ApartmentQuoteService;
use App\Services\CloudbedsApiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class CloudbedsPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['cloudbeds.api.key' => 'cloudbeds-test-key', 'cloudbeds.api.property_id' => 'property-1', 'cloudbeds.api.base_url' => 'https://api.cloudbeds.com/api/v1.3']);
        Cache::put('currency.usd-ngn', 1500);
        Http::preventStrayRequests();
        $this->fakeRates();
    }

    public function test_cloudbeds_total_is_used_instead_of_local_prices_and_peak_overrides(): void
    {
        $apartment = $this->apartment();
        $apartment->update(['sale_price' => 10]);
        PeakPeriod::create(['name' => 'Local peak', 'start_date' => '2027-03-10', 'end_date' => '2027-03-12', 'increase_percent' => 100, 'is_active' => true, 'apartment_prices' => [$apartment->id => '999.00']]);
        $quote = $this->quote($apartment);
        $this->assertSame(876.0, $quote['total_usd']);
        $this->assertSame(438.0, $quote['nightly']);
        $this->assertSame('cloudbeds', $quote['rate_source']);
        $this->assertSame('base-rate', $quote['source_rate_id']);
    }

    public function test_local_percentage_discount_is_applied_to_the_current_cloudbeds_price(): void
    {
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 10]);
        $quote = $this->quote($apartment);
        $this->assertSame(876.0, $quote['original_total_usd']);
        $this->assertSame(788.4, $quote['total_usd']);
        $this->assertSame(394.2, $quote['nightly']);
        $this->assertSame(10, $quote['promotion']['percentage']);
    }

    public function test_an_apartment_discount_overrides_a_global_discount_without_stacking(): void
    {
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 20]);
        $this->promotion(['scope' => 'apartment', 'apartment_id' => $apartment->id, 'discount_value' => 10]);
        $this->assertSame(788.4, $this->quote($apartment)['total_usd']);
    }

    public function test_an_expired_local_discount_is_not_applied(): void
    {
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 10, 'ends_on' => now()->subDay()->toDateString()]);
        $this->assertSame(876.0, $this->quote($apartment)['total_usd']);
    }

    public function test_cloudbeds_rate_updates_are_seen_on_the_next_request(): void
    {
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 10]);
        $this->fakeRates(Http::sequence()->push(['success' => true, 'data' => [$this->rate(876)]])->push(['success' => true, 'data' => [$this->rate(1000)]]));
        $this->assertSame(788.4, $this->quote($apartment)['total_usd']);
        $this->assertSame(900.0, $this->quote($apartment)['total_usd']);
        Http::assertSentCount(4);
    }

    public function test_a_single_request_uses_one_stable_rate_for_display_and_payment_conversion(): void
    {
        $apartment = $this->apartment();
        $service = app(ApartmentQuoteService::class);
        $usd = $service->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), $this->usd());
        $ngn = $service->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500]);
        $this->assertSame(876.0, $usd['total']);
        $this->assertSame(1314000.0, $ngn['total']);
        Http::assertSentCount(2);
    }

    public function test_total_rate_includes_guest_supplements_and_is_not_multiplied_by_nights(): void
    {
        $apartment = $this->apartment();
        $this->fakeRates(['success' => true, 'data' => [$this->rate(920) + ['roomRate' => 876]]]);
        $quote = app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), $this->usd(), 3);
        $this->assertSame(920.0, $quote['total_usd']);
        $this->assertSame(460.0, $quote['nightly']);
        Http::assertSent(fn ($request): bool => str_ends_with(strtok($request->url(), '?'), '/getRatePlans') && (int) $request['adults'] === 3 && $request['detailedRates'] === 'true');
    }

    public function test_additional_cloudbeds_rate_plans_do_not_replace_the_base_rate(): void
    {
        $apartment = $this->apartment();
        $this->fakeRates(['success' => true, 'data' => [$this->rate(400) + ['ratePlanID' => 'other-plan'], $this->rate(876)]]);
        $this->assertSame(876.0, $this->quote($apartment)['total_usd']);
    }

    public function test_cloudbeds_ngn_currency_is_converted_without_treating_naira_as_dollars(): void
    {
        $apartment = $this->apartment();
        $this->fakeRates(['success' => true, 'data' => [$this->rate(600000)]], 'NGN');
        $this->assertSame(400.0, $this->quote($apartment)['total_usd']);
    }

    public function test_a_local_fixed_sale_price_only_applies_when_it_reduces_the_live_quote(): void
    {
        $apartment = $this->apartment();
        $promotion = $this->promotion(['discount_type' => 'fixed_price', 'discount_value' => 600000]);
        $this->assertSame(800.0, $this->quote($apartment)['total_usd']);
        $promotion->update(['discount_value' => 900000]);
        $this->assertSame(876.0, $this->quote($apartment)['total_usd']);
    }

    public function test_no_date_selection_does_not_display_a_stale_local_price(): void
    {
        $quote = app(ApartmentQuoteService::class)->quote($this->apartment(), null, null, $this->usd());
        $this->assertFalse($quote['price_available']);
        $this->assertSame(0.0, $quote['total']);
        Http::assertNothingSent();
    }

    public function test_unmapped_apartment_is_not_quoted_using_the_local_price(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cloudbeds mapping');
        $this->quote($this->apartment('Unmapped'));
    }

    public function test_missing_rate_or_api_failure_never_falls_back_to_local_prices(): void
    {
        $this->fakeRates(['success' => false, 'message' => 'Unavailable']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Live Cloudbeds rates');
        $this->quote($this->apartment());
    }

    public function test_an_explicit_room_type_id_maps_an_apartment_with_a_different_name(): void
    {
        $apartment = $this->apartment('Local display name');
        $apartment->update(['apartment_id' => 'room-1']);
        $this->assertSame(876.0, $this->quote($apartment)['total_usd']);
    }

    public function test_search_results_show_the_discounted_cloudbeds_price(): void
    {
        $this->apartment();
        $this->promotion(['discount_value' => 10]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypes')->once()->andReturn([['id' => 'room-1', 'name' => 'Beaumont', 'full_name' => 'Beaumont', 'room_type_ids' => ['room-1'], 'available' => true]]);
        });
        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('apartments.index', [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'currency' => 'USD',
        ]))->assertOk()->assertSee('$394.20')->assertSee('$438')->assertDontSee('$200');
    }

    public function test_checkout_charges_the_discounted_cloudbeds_rate_not_the_local_rate(): void
    {
        config(['services.paystack.public_key' => 'pk_test_live_prices']);
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 10]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => 'room-1']);
        });
        $this->postJson(route('reservations.store', $apartment), [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'guests' => 1,
            'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'test@example.com',
            'phone_country' => 'NG', 'phone' => '8012345678',
            'identity_document' => UploadedFile::fake()->image('guest-id.jpg'),
        ])->assertOk()->assertJsonPath('payment.amount', 118260000)
            ->assertJsonPath('payment.metadata.booking.rate_source', 'cloudbeds');
    }

    public function test_unmapped_cloudbeds_rooms_do_not_replace_uploaded_cards_with_generic_cards(): void
    {
        $this->promotion(['discount_value' => 10]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypes')->once()->andReturn([['id' => 'room-1', 'name' => 'Beaumont', 'full_name' => 'Beaumont', 'room_type_ids' => ['room-1'], 'available' => true]]);
        });
        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('apartments.index', [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'currency' => 'USD',
        ]))->assertOk()->assertSee('There are no residences available')
            ->assertDontSee('data-apartment-card', false)->assertDontSee('$438');
        $this->assertDatabaseCount('apartments', 0);
        Http::assertNothingSent();
    }

    public function test_explicit_cloudbeds_mapping_keeps_all_locally_uploaded_card_content(): void
    {
        $apartment = $this->apartment('Our Local Residence');
        $apartment->update([
            'apartment_id' => 'room-1', 'no_of_rooms' => 3, 'toilets' => 3.5,
            'max_adults' => 7, 'bedroom_1' => 'King bed', 'bedroom_2' => 'King bed', 'bedroom_3' => 'King bed',
        ]);
        $apartment->images()->create(['image' => 'uploads/apartments/local-living-room.jpg', 'caption' => 'Local living room', 'image_id' => 1]);
        $apartment->images()->create(['image' => 'uploads/apartments/local-bedroom.jpg', 'caption' => 'Local bedroom', 'image_id' => 2]);
        $group = Attribute::create(['name' => 'Internet', 'slug' => 'internet', 'type' => 'apartment_facility']);
        foreach (['Parking included', 'Free WiFi'] as $name) {
            $amenity = Attribute::create(['name' => $name, 'slug' => str($name)->slug(), 'type' => 'apartment_facility', 'parent_id' => $group->id]);
            $apartment->attributes()->attach($amenity);
        }
        $this->promotion(['discount_value' => 17]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Upstream card name', 'description' => 'Upstream description must not replace local uploads']]);

        $this->search()->assertOk()->assertSee('Our Local Residence')
            ->assertSee('uploads/apartments/local-living-room.jpg', false)->assertSee('Local living room')
            ->assertSee('uploads/apartments/local-bedroom.jpg', false)->assertSee('View all 2 photos')
            ->assertSee('Parking included')->assertSee('Free WiFi')->assertSee('3 Bedrooms')
            ->assertSee('3.5 Bathrooms')->assertSee('Sleeps 7')->assertSee('3 King Beds')
            ->assertSee('Save 17%')->assertSee('<del>$438</del>', false)->assertSee('$363.54')
            ->assertSee('Reserve now')->assertSee('More details')
            ->assertDontSee('Upstream card name')->assertDontSee('Upstream description');
        $this->assertSame('Our Local Residence', $apartment->fresh()->name);
        $this->assertDatabaseCount('images', 2);
    }

    public function test_availability_and_prices_use_the_same_room_id_even_when_upstream_labels_differ(): void
    {
        $this->apartment();
        $this->fakeRates(['success' => true, 'data' => [array_replace($this->rate(876), ['roomTypeName' => 'Rates endpoint label'])]]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Beaumont']]);
        $this->search()->assertOk()->assertSee('Beaumont')->assertSee('$438')->assertDontSee('Rates endpoint label');
    }

    public function test_explicit_id_mapping_takes_priority_over_another_local_records_name_match(): void
    {
        $this->apartment()->update(['sort_order' => 1]);
        $this->apartment('Explicitly Mapped Residence')->update(['apartment_id' => 'room-1', 'sort_order' => 2]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Beaumont']]);
        $response = $this->search()->assertOk()->assertSee('Explicitly Mapped Residence')->assertDontSee('Beaumont');
        $this->assertSame(1, substr_count($response->getContent(), '<article class="residence-card"'));
    }

    public function test_a_missing_rate_for_the_mapped_room_does_not_use_another_rooms_similar_name(): void
    {
        $this->apartment();
        $this->fakeRates(['success' => true, 'data' => [array_replace($this->rate(876), ['roomTypeID' => 'different-room'])]]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Beaumont']]);
        $this->search()->assertOk()->assertSee('Beaumont')->assertSee('Live prices could not be confirmed')
            ->assertDontSee('$438')->assertDontSee('Reserve now');
    }

    public function test_penthouse_alias_keeps_the_uploaded_name_instead_of_renaming_it_from_cloudbeds(): void
    {
        $apartment = $this->apartment('Belvedere - Penthouse');
        $this->fakeRates(['success' => true, 'data' => [array_replace($this->rate(1754), ['roomTypeID' => 'penthouse-1', 'roomTypeName' => 'PEN'])]]);
        $this->availableRooms([['id' => 'penthouse-1', 'name' => 'Pen', 'full_name' => 'Pent House']]);
        $this->search()->assertOk()->assertSee('Belvedere - Penthouse')->assertSee('$877');
        $this->assertSame('Belvedere - Penthouse', $apartment->fresh()->name);
    }

    public function test_local_card_order_is_preserved_and_only_available_mapped_apartments_are_shown(): void
    {
        $first = $this->apartment('Belvedere - Penthouse');
        $first->update(['sort_order' => 1]);
        $second = $this->apartment();
        $second->update(['sort_order' => 2]);
        $this->apartment('Unavailable local apartment');
        $this->fakeRates(['success' => true, 'data' => [$this->rate(876), array_replace($this->rate(1754), ['roomTypeID' => 'penthouse-1', 'roomTypeName' => 'Pent House'])]]);
        $this->availableRooms([
            ['id' => 'room-1', 'name' => 'Beaumont'],
            ['id' => 'penthouse-1', 'name' => 'Pen'],
            ['id' => 'unmapped-1', 'name' => 'API-only room'],
        ]);
        $this->search()->assertOk()->assertSeeInOrder(['Belvedere - Penthouse', 'Beaumont'])
            ->assertDontSee('Unavailable local apartment')->assertDontSee('API-only room');
    }

    public function test_nigerian_cards_convert_discounted_cloudbeds_usd_prices_using_the_existing_fx_api(): void
    {
        $this->apartment();
        $this->promotion(['discount_value' => 17]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Beaumont']]);
        Cache::forget('currency.usd-ngn');
        Http::fake(['https://api.exchangerate-api.com/v4/latest/USD' => Http::response(['rates' => ['NGN' => 1500]])]);
        $this->withHeader('CF-IPCountry', 'NG')->search(null)->assertOk()
            ->assertSee('Save 17%')->assertSee('<del>₦657,000</del>', false)->assertSee('₦545,310')
            ->assertDontSee('$363.54')->assertSessionHas('currency.code', 'NGN');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.exchangerate-api.com/v4/latest/USD');
    }

    public function test_non_nigerian_cards_keep_the_discounted_cloudbeds_price_in_usd(): void
    {
        $this->apartment();
        $this->promotion(['discount_value' => 17]);
        $this->availableRooms([['id' => 'room-1', 'name' => 'Beaumont']]);
        $this->withHeader('CF-IPCountry', 'GB')->search(null)->assertOk()
            ->assertSee('$363.54')->assertSee('<del>$438</del>', false)
            ->assertDontSee('₦545,310')->assertSessionHas('currency.code', 'USD');
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'exchangerate-api.com'));
    }

    private function availableRooms(array $rooms): void
    {
        $this->partialMock(CloudbedsApiService::class, function ($mock) use ($rooms): void {
            $mock->shouldReceive('availableRoomTypes')->once()->andReturn($rooms);
        });
    }

    private function search(?string $currency = 'USD'): TestResponse
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('apartments.index', array_filter([
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'currency' => $currency,
        ])));
    }

    public function test_missing_cloudbeds_currency_is_not_silently_assumed_to_be_usd(): void
    {
        $this->fakeRates(null, '');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('pricing currency');
        $this->quote($this->apartment());
    }

    public function test_guest_count_is_preserved_in_the_checkout_form(): void
    {
        $apartment = $this->apartment();
        $this->get(route('reservations.create', $apartment).'?'.http_build_query([
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'guests' => 3, 'currency' => 'USD',
        ]))->assertOk()->assertSee('name="guests" value="3"', false);
        Http::assertSent(fn ($request): bool => str_ends_with(strtok($request->url(), '?'), '/getRatePlans') && (int) $request['adults'] === 3);
    }

    public function test_guest_supplemented_live_rate_is_used_for_payment(): void
    {
        config(['services.paystack.public_key' => 'pk_test_live_prices']);
        $apartment = $this->apartment();
        $this->promotion(['discount_value' => 10]);
        $this->fakeRates(['success' => true, 'data' => [$this->rate(920) + ['roomRate' => 876]]]);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => 'room-1']);
        });
        $this->postJson(route('reservations.store', $apartment), [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'guests' => 3,
            'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'test@example.com', 'phone_country' => 'NG', 'phone' => '8012345678',
            'identity_document' => UploadedFile::fake()->image('guest-id.jpg'),
        ])->assertOk()->assertJsonPath('payment.amount', 124200000)->assertJsonPath('payment.metadata.booking.guests', 3);
    }

    public function test_payment_is_not_started_when_a_live_rate_cannot_be_confirmed(): void
    {
        $apartment = $this->apartment();
        $this->fakeRates(['success' => false, 'message' => 'Unavailable']);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => 'room-1']);
        });
        $this->postJson(route('reservations.store', $apartment), [
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'guests' => 1,
            'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'test@example.com', 'phone_country' => 'NG', 'phone' => '8012345678',
            'identity_document' => UploadedFile::fake()->image('guest-id.jpg'),
        ])->assertStatus(503)->assertJsonMissingPath('payment');
        $this->assertDatabaseCount('invoices', 0);
    }

    private function fakeRates(mixed $response = null, string $currency = 'USD'): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.cloudbeds.com/api/v1.3/getHotels*' => Http::response(['success' => true, 'data' => [['propertyID' => 'property-1', 'propertyCurrency' => ['currencyCode' => $currency]]]]),
            'https://api.cloudbeds.com/api/v1.3/getRatePlans*' => $response ?? Http::response(['success' => true, 'data' => [$this->rate(876)]]),
        ]);
    }

    private function rate(float $total): array
    {
        return ['roomTypeID' => 'room-1', 'roomTypeName' => 'Beaumont', 'rateID' => 'base-rate', 'totalRate' => $total];
    }

    private function apartment(string $name = 'Beaumont'): Apartment
    {
        return Apartment::create(['name' => $name, 'slug' => str($name)->slug(), 'price' => 200, 'allow' => true, 'max_adults' => 4]);
    }

    private function promotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_replace(['name' => 'Local sale', 'scope' => 'global', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true], $overrides));
    }

    private function usd(): array
    {
        return ['code' => 'USD', 'symbol' => '$', 'rate' => 1];
    }

    private function quote(Apartment $apartment): array
    {
        return app(ApartmentQuoteService::class)->quote($apartment, Carbon::parse('2027-03-10'), Carbon::parse('2027-03-12'), $this->usd());
    }
}
