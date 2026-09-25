<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentDateBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApartmentDateBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_multiple_apartments_without_creating_a_reservation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $first = $this->apartment('Berkeley');
        $second = $this->apartment('Stanmore');

        $this->actingAs($admin)
            ->post(route('admin.date-blocks.store'), [
                'title' => 'Owner stay',
                'starts_on' => '2027-03-10',
                'ends_on' => '2027-03-15',
                'reason' => 'Not available to guests.',
                'apartment_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('admin.date-blocks.index'));

        $block = ApartmentDateBlock::query()->firstOrFail();

        $this->assertSame('Owner stay', $block->title);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $block->apartments()->pluck('apartments.id')->all());
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_blocked_apartment_is_excluded_from_overlapping_searches(): void
    {
        $blocked = $this->apartment('Blocked Residence');
        $available = $this->apartment('Available Residence');
        $block = ApartmentDateBlock::create([
            'title' => 'Maintenance',
            'starts_on' => '2027-03-10',
            'ends_on' => '2027-03-15',
        ]);
        $block->apartments()->attach($blocked);

        $this->get(route('apartments.index', [
            'search' => 1,
            'checkin' => '2027-03-11',
            'checkout' => '2027-03-13',
        ]))
            ->assertOk()
            ->assertDontSee($blocked->name)
            ->assertSee($available->name);

        $this->postJson(route('apartments.availability', $blocked), [
            'checkin' => '2027-03-11',
            'checkout' => '2027-03-13',
            'guests' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('reserve_url', null);
    }

    public function test_admin_can_remove_a_date_block(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $apartment = $this->apartment('Beaufort');
        $block = ApartmentDateBlock::create([
            'title' => 'Maintenance',
            'starts_on' => '2027-03-10',
            'ends_on' => '2027-03-15',
        ]);
        $block->apartments()->attach($apartment);

        $this->actingAs($admin)
            ->delete(route('admin.date-blocks.destroy', $block))
            ->assertRedirect(route('admin.date-blocks.index'));

        $this->assertDatabaseMissing('apartment_date_blocks', ['id' => $block->id]);
        $this->assertDatabaseCount('apartment_date_block', 0);
    }

    private function apartment(string $name): Apartment
    {
        return Apartment::create([
            'name' => $name,
            'slug' => str($name)->slug(),
            'price' => 500,
            'allow' => true,
            'max_adults' => 4,
            'no_of_rooms' => 2,
        ]);
    }
}
