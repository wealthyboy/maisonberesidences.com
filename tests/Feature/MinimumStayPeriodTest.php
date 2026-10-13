<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentDateBlock;
use App\Models\MinimumStayPeriod;
use App\Models\User;
use App\Services\CloudbedsApiService;
use App\Services\MinimumStayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MinimumStayPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudbeds.api.key' => null]);
        Cache::put('currency.usd-ngn', 1500);
        Http::preventStrayRequests();
    }

    public function test_admin_can_add_a_global_december_minimum_without_creating_a_block_or_changing_prices(): void
    {
        $apartment = $this->apartment();
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.minimum-stays.store'), $this->form())
            ->assertRedirect(route('admin.date-blocks.index'));
        $rule = MinimumStayPeriod::firstOrFail();
        $this->assertSame(4, $rule->minimum_nights);
        $this->assertTrue($rule->applies_to_all);
        $this->assertSame(500.0, (float) $apartment->fresh()->price);
        $this->assertDatabaseCount('apartment_date_blocks', 0);
        $this->assertDatabaseCount('invoices', 0);
        Http::assertNothingSent();
    }

    public function test_admin_can_target_specific_apartments_and_update_the_range_and_minimum(): void
    {
        $apartment = $this->apartment();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('admin.minimum-stays.store'), $this->form([
            'minimum_scope' => 'selected', 'minimum_apartment_ids' => [$apartment->id],
        ]))->assertRedirect();
        $rule = MinimumStayPeriod::firstOrFail();
        $this->assertFalse($rule->applies_to_all);
        $this->assertEquals([$apartment->id], $rule->apartments()->pluck('apartments.id')->all());
        $this->put(route('admin.minimum-stays.update', $rule), $this->form([
            'minimum_nights' => 6, 'minimum_starts_on' => '2027-12-15', 'minimum_is_active' => 0,
        ]))->assertRedirect();
        $this->assertSame(6, $rule->fresh()->minimum_nights);
        $this->assertSame('2027-12-15', $rule->fresh()->starts_on->toDateString());
        $this->assertFalse($rule->fresh()->is_active);
        $this->assertDatabaseCount('apartment_minimum_stay_period', 0);
    }

    public function test_admin_calendar_page_contains_create_and_edit_controls(): void
    {
        $this->apartment();
        $this->period();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.date-blocks.index'))
            ->assertOk()->assertSee('Calendar rules')->assertSee('Minimum 4 nights')
            ->assertSee('Add minimum-stay rule')->assertSee('Save rule changes')
            ->assertSee('Block selected apartments')->assertSee('Mirror Cloudbeds blocks');
    }

    public function test_invalid_ranges_night_counts_and_apartment_selections_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([
            ['minimum_nights' => 0], ['minimum_nights' => 2.5], ['minimum_nights' => -2],
            ['minimum_ends_on' => '2027-11-30'],
            ['minimum_scope' => 'selected', 'minimum_apartment_ids' => []],
            ['minimum_scope' => 'selected', 'minimum_apartment_ids' => [99999]],
        ] as $invalid) {
            $this->postJson(route('admin.minimum-stays.store'), $this->form($invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('minimum_stay_periods', 0);
    }

    public function test_non_admin_cannot_change_calendar_rules(): void
    {
        $rule = $this->period();
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->postJson(route('admin.minimum-stays.store'), $this->form())->assertNotFound();
        $this->putJson(route('admin.minimum-stays.update', $rule), $this->form())->assertNotFound();
        $this->deleteJson(route('admin.minimum-stays.destroy', $rule))->assertNotFound();
    }

    public function test_removing_a_minimum_stay_rule_does_not_remove_an_existing_date_block(): void
    {
        $apartment = $this->apartment();
        $rule = $this->period(['applies_to_all' => false]);
        $rule->apartments()->attach($apartment);
        ApartmentDateBlock::create(['title' => 'Maintenance', 'starts_on' => '2027-12-01', 'ends_on' => '2027-12-04']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->delete(route('admin.minimum-stays.destroy', $rule))->assertRedirect();
        $this->assertDatabaseCount('minimum_stay_periods', 0);
        $this->assertDatabaseCount('apartment_minimum_stay_period', 0);
        $this->assertDatabaseCount('apartment_date_blocks', 1);
    }

    public function test_december_requires_four_nights_but_outside_the_range_defaults_to_one(): void
    {
        $this->period();
        $this->assertSame(4, $this->minimum('2027-12-10', '2027-12-13'));
        $this->assertSame(1, $this->minimum('2028-01-01', '2028-01-02'));
        $this->assertSame(1, $this->minimum('2027-11-10', '2027-11-11'));
    }

    public function test_any_booked_night_in_the_range_applies_but_checkout_is_excluded(): void
    {
        $this->period();
        $this->assertSame(1, $this->minimum('2027-11-30', '2027-12-01'));
        $this->assertSame(4, $this->minimum('2027-11-30', '2027-12-02'));
        $this->assertSame(4, $this->minimum('2027-12-31', '2028-01-01'));
    }

    public function test_overlapping_rules_use_the_highest_minimum_and_ignore_inactive_rules(): void
    {
        $this->period();
        $this->period(['title' => 'Christmas', 'starts_on' => '2027-12-20', 'ends_on' => '2027-12-26', 'minimum_nights' => 6]);
        $this->period(['minimum_nights' => 10, 'is_active' => false]);
        $this->assertSame(6, $this->minimum('2027-12-19', '2027-12-24'));
        $this->assertSame(4, $this->minimum('2027-12-01', '2027-12-05'));
    }

    public function test_specific_rules_only_apply_to_selected_apartments_and_global_rules_include_future_uploads(): void
    {
        $target = $this->apartment();
        $other = $this->apartment('Berkeley');
        $specific = $this->period(['applies_to_all' => false]);
        $specific->apartments()->attach($target);
        $this->assertSame(4, $this->minimum('2027-12-10', '2027-12-12', $target));
        $this->assertSame(1, $this->minimum('2027-12-10', '2027-12-12', $other));
        $this->assertSame(1, $this->minimum('2027-12-10', '2027-12-12'));
        $this->period(['minimum_nights' => 3]);
        $futureUpload = $this->apartment('Future Upload');
        $this->assertSame(3, $this->minimum('2027-12-10', '2027-12-12', $futureUpload));
    }

    public function test_one_day_and_cross_year_rule_ranges_are_supported(): void
    {
        $this->period(['starts_on' => '2027-12-31', 'ends_on' => '2027-12-31', 'minimum_nights' => 2]);
        $this->assertSame(2, $this->minimum('2027-12-31', '2028-01-02'));
        $this->assertSame(1, $this->minimum('2028-01-01', '2028-01-02'));
        $this->period(['starts_on' => '2027-12-24', 'ends_on' => '2028-01-05', 'minimum_nights' => 5]);
        $this->assertSame(5, $this->minimum('2028-01-03', '2028-01-08'));
    }

    public function test_global_rules_reject_short_searches_before_contacting_cloudbeds(): void
    {
        $this->period();
        $this->getJson(route('apartments.index', ['checkin' => '2027-12-10', 'checkout' => '2027-12-13']))
            ->assertUnprocessable()->assertJsonValidationErrors('checkout')
            ->assertSee('minimum stay for these dates is 4 nights');
        Http::assertNothingSent();
    }

    public function test_specific_rules_filter_short_searches_without_hiding_other_qualifying_apartments(): void
    {
        $target = $this->apartment();
        $other = $this->apartment('Berkeley');
        $this->period(['applies_to_all' => false])->apartments()->attach($target);
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypes')->once()->andReturn([
                ['id' => '1', 'name' => 'Beaufort'], ['id' => '2', 'name' => 'Berkeley'],
            ]);
        });
        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('apartments.index', ['checkin' => '2027-12-10', 'checkout' => '2027-12-13']))
            ->assertOk()->assertSee('Beaufort requires a minimum of 4 nights')->assertSee('Berkeley')
            ->assertViewHas('residences', fn ($residences): bool => $residences->count() === 1 && $residences->first()['apartment']->id === $other->id);
    }

    public function test_apartment_availability_rejects_a_short_stay_without_an_api_call(): void
    {
        $apartment = $this->apartment();
        $this->period(['applies_to_all' => false])->apartments()->attach($apartment);
        $this->postJson(route('apartments.availability', $apartment), ['checkin' => '2027-12-10', 'checkout' => '2027-12-13'])
            ->assertUnprocessable()->assertJsonValidationErrors('checkout');
        Http::assertNothingSent();
    }

    public function test_a_qualifying_stay_is_not_treated_as_a_date_block(): void
    {
        $apartment = $this->apartment();
        $this->period();
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->once()->andReturn(['id' => '1']);
        });
        $this->postJson(route('apartments.availability', $apartment), ['checkin' => '2027-12-10', 'checkout' => '2027-12-14'])
            ->assertOk()->assertJsonPath('available', true);
        $this->assertFalse($apartment->isBlockedFor(Carbon::parse('2027-12-10'), Carbon::parse('2027-12-14')));
    }

    public function test_direct_checkout_and_coupon_requests_cannot_bypass_a_specific_minimum(): void
    {
        $apartment = $this->apartment();
        $this->period(['applies_to_all' => false])->apartments()->attach($apartment);
        $stay = ['checkin' => '2027-12-10', 'checkout' => '2027-12-13'];
        $this->getJson(route('reservations.create', $apartment).'?'.http_build_query($stay))->assertUnprocessable()->assertJsonValidationErrors('checkout');
        $this->postJson(route('reservations.store', $apartment), $stay)->assertUnprocessable()->assertJsonMissingPath('payment');
        $this->postJson(route('reservations.coupon', $apartment), $stay)->assertUnprocessable()->assertJsonValidationErrors('checkout');
        Http::assertNothingSent();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_four_nights_and_longer_are_accepted_and_one_night_is_allowed_after_december(): void
    {
        $apartment = $this->apartment();
        $this->period();
        foreach ([['2027-12-10', '2027-12-14'], ['2027-12-10', '2027-12-16'], ['2028-01-01', '2028-01-02']] as [$checkin, $checkout]) {
            $this->get(route('reservations.create', $apartment).'?'.http_build_query(compact('checkin', 'checkout')))
                ->assertOk()->assertSee('Guest details');
        }
    }

    public function test_the_calendar_contains_only_applicable_active_rules(): void
    {
        $apartment = $this->apartment();
        $this->period();
        $specific = $this->period(['minimum_nights' => 6, 'applies_to_all' => false]);
        $specific->apartments()->attach($apartment);
        $this->period(['minimum_nights' => 20, 'is_active' => false]);
        $global = app(MinimumStayService::class)->calendarRulesFor();
        $apartmentRules = app(MinimumStayService::class)->calendarRulesFor($apartment);
        $this->assertCount(1, $global);
        $this->assertCount(2, $apartmentRules);
        $this->get(route('apartments.show', $apartment))->assertOk()
            ->assertSee('"minimum_nights":4', false)->assertSee('"minimum_nights":6', false)
            ->assertDontSee('"minimum_nights":20', false);
    }

    private function apartment(string $name = 'Beaufort'): Apartment
    {
        return Apartment::create(['name' => $name, 'slug' => str($name)->slug(), 'price' => 500, 'allow' => true, 'max_adults' => 7]);
    }

    private function period(array $overrides = []): MinimumStayPeriod
    {
        return MinimumStayPeriod::create(array_replace([
            'title' => 'December peak', 'starts_on' => '2027-12-01', 'ends_on' => '2027-12-31',
            'minimum_nights' => 4, 'applies_to_all' => true, 'is_active' => true,
        ], $overrides));
    }

    private function minimum(string $checkin, string $checkout, ?Apartment $apartment = null): int
    {
        return app(MinimumStayService::class)->minimumNightsFor(Carbon::parse($checkin), Carbon::parse($checkout), $apartment);
    }

    private function form(array $overrides = []): array
    {
        return array_replace([
            'minimum_title' => 'December peak', 'minimum_starts_on' => '2027-12-01', 'minimum_ends_on' => '2027-12-31',
            'minimum_nights' => 4, 'minimum_scope' => 'all', 'minimum_is_active' => 1,
        ], $overrides);
    }
}
