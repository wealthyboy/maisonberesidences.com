<?php

namespace Tests\Feature;

use App\Mail\ReservationReceiptMail;
use App\Models\AdditionalService;
use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AdditionalServiceQuoteService;
use App\Services\PaystackBookingService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class AdditionalServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_service_for_selected_apartments(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $stanmore = Apartment::create(['name' => 'Stanmore', 'slug' => 'stanmore', 'price' => 500]);
        $berkeley = Apartment::create(['name' => 'Berkeley', 'slug' => 'berkeley', 'price' => 450]);

        $response = $this->actingAs($admin)->post(route('admin.modules.store', 'additional-services'), [
            'name' => 'Breakfast',
            'description' => 'Daily breakfast service.',
            'price_usd' => 25,
            'is_active' => 1,
            'available_for_all_apartments' => 0,
            'sort_order' => 1,
            'apartment_ids' => [$stanmore->id],
        ]);

        $service = AdditionalService::with('apartments')->firstOrFail();

        $response->assertRedirect(route('admin.modules.record.show', ['additional-services', $service->id]));
        $this->assertSame('Breakfast', $service->name);
        $this->assertFalse($service->available_for_all_apartments);
        $this->assertTrue($service->apartments->contains($stanmore));
        $this->assertFalse($service->apartments->contains($berkeley));
    }

    public function test_services_are_filtered_by_apartment_and_converted_from_usd(): void
    {
        $stanmore = Apartment::create(['name' => 'Stanmore', 'slug' => 'stanmore', 'price' => 500]);
        $berkeley = Apartment::create(['name' => 'Berkeley', 'slug' => 'berkeley', 'price' => 450]);
        $breakfast = AdditionalService::create([
            'name' => 'Breakfast',
            'slug' => 'breakfast',
            'price_usd' => 20,
            'is_active' => true,
            'available_for_all_apartments' => false,
        ]);
        $breakfast->apartments()->attach($stanmore);

        $quote = app(AdditionalServiceQuoteService::class)->quoteSelection(
            $stanmore,
            [$breakfast->id => 2],
            ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500, 'country' => 'Nigeria'],
        );
        $unavailableQuote = app(AdditionalServiceQuoteService::class)->quoteSelection(
            $berkeley,
            [$breakfast->id => 2],
            ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1500, 'country' => 'Nigeria'],
        );

        $this->assertSame(60000.0, $quote['subtotal']);
        $this->assertSame(30000.0, $quote['items'][0]['unit_price']);
        $this->assertSame([], $unavailableQuote['items']);
    }

    public function test_receipt_displays_purchased_services(): void
    {
        $apartment = Apartment::create(['name' => 'Stanmore', 'slug' => 'stanmore', 'price' => 500]);
        $invoice = Invoice::create([
            'invoice' => 'MBR-TEST-001',
            'full_name' => 'Test Guest',
            'currency' => '$',
            'currency_code' => 'USD',
            'exchange_rate' => 1,
            'subtotal' => 550,
            'discount' => 0,
            'vat_rate' => 7.5,
            'vat_amount' => 37.5,
            'total' => 587.5,
            'payment_status' => 'paid',
        ]);
        $invoice->invoiceItems()->create([
            'apartment_id' => $apartment->id,
            'name' => $apartment->name,
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
            'checkin' => now()->addDay(),
            'checkout' => now()->addDays(2),
        ]);
        $invoice->serviceItems()->create([
            'apartment_id' => $apartment->id,
            'name' => 'Breakfast',
            'quantity' => 2,
            'unit_price' => 25,
            'total' => 50,
            'unit_price_usd' => 25,
        ]);

        $this->get(route('reservations.receipt', $invoice))
            ->assertOk()
            ->assertSee('Additional services')
            ->assertSee('Breakfast')
            ->assertSee('$50')
            ->assertSee('VAT (7.5%)')
            ->assertSee('$37.50');
    }

    public function test_checkout_adds_selected_services_to_the_server_generated_payment_total(): void
    {
        Http::fake([
            '*' => Http::response(['rates' => ['NGN' => 1500]], 200),
        ]);

        $apartment = Apartment::create(['name' => 'Stanmore', 'slug' => 'stanmore', 'price' => 500]);
        $breakfast = AdditionalService::create([
            'name' => 'Breakfast',
            'slug' => 'breakfast',
            'price_usd' => 20,
            'is_active' => true,
            'available_for_all_apartments' => true,
        ]);

        $response = $this->postJson(route('reservations.store', $apartment), [
            'checkin' => now()->addDays(10)->toDateString(),
            'checkout' => now()->addDays(11)->toDateString(),
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => 'guest@example.com',
            'country_code' => '+234',
            'phone' => '8012345678',
            'country' => 'Nigeria',
            'services' => [$breakfast->id => 2],
        ]);

        $response->assertOk()
            ->assertJsonPath('payment.currency', 'NGN')
            ->assertJsonPath('payment.amount', 86625000)
            ->assertJsonPath('payment.metadata.booking.vat_rate', 7.5)
            ->assertJsonPath('payment.metadata.booking.vat_amount', 56250)
            ->assertJsonPath('payment.metadata.booking.services.0.name', 'Breakfast')
            ->assertJsonPath('payment.metadata.booking.services.0.quantity', 2)
            ->assertJsonPath('payment.metadata.booking.services_subtotal', 60000);
    }

    public function test_confirmed_payment_persists_service_lines_on_the_invoice(): void
    {
        Mail::fake();

        $apartment = Apartment::create(['name' => 'Stanmore', 'slug' => 'stanmore', 'price' => 500]);
        $breakfast = AdditionalService::create([
            'name' => 'Breakfast',
            'slug' => 'breakfast',
            'price_usd' => 20,
            'is_active' => true,
            'available_for_all_apartments' => true,
        ]);
        $booking = [
            'invoice_number' => 'MBR-SERVICE-001',
            'full_name' => 'Test Guest',
            'email' => 'guest@example.com',
            'phone' => '+234 8012345678',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'currency_symbol' => '₦',
            'exchange_rate' => 1500,
            'subtotal' => 810000,
            'accommodation_subtotal' => 750000,
            'services_subtotal' => 60000,
            'discount' => 0,
            'vat_rate' => 7.5,
            'vat_amount' => 56250,
            'discount_type' => 'fixed',
            'coupon' => null,
            'total' => 866250,
            'length_of_stay' => 1,
            'from' => now()->addDays(10)->toDateString(),
            'to' => now()->addDays(11)->toDateString(),
            'apartment_id' => $apartment->id,
            'apartment_name' => $apartment->name,
            'services' => [[
                'additional_service_id' => $breakfast->id,
                'name' => 'Breakfast',
                'quantity' => 2,
                'unit_price_usd' => 20,
                'unit_price' => 30000,
                'total' => 60000,
            ]],
        ];
        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verify')->once()->with('MBR-PAYMENT-001')->andReturn([
            'status' => 'success',
            'amount' => 86625000,
            'currency' => 'NGN',
            'metadata' => ['booking' => $booking],
        ]);

        $invoice = (new PaystackBookingService($paystack))->processReference('MBR-PAYMENT-001');

        $this->assertSame('810000.00', $invoice->subtotal);
        $this->assertSame('7.50', $invoice->vat_rate);
        $this->assertSame('56250.00', $invoice->vat_amount);
        $this->assertSame('866250.00', $invoice->total);
        $this->assertCount(1, $invoice->serviceItems);
        $this->assertSame('Breakfast', $invoice->serviceItems->first()->name);
        $this->assertSame(2, $invoice->serviceItems->first()->quantity);
        $this->assertSame('60000.00', $invoice->serviceItems->first()->total);
        Mail::assertSent(ReservationReceiptMail::class, fn (ReservationReceiptMail $mail) =>
            $mail->hasTo('guest@example.com')
            && $mail->hasBcc('reservations@maisonberesidences.com')
            && $mail->hasBcc('md@maisonberesidences.com')
            && $mail->hasBcc('info@maisonberesidences.com')
        );
    }
}
