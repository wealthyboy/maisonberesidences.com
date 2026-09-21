<?php

namespace Tests\Feature;

use App\Mail\SelfCheckInSubmissionMail;
use App\Mail\SelfCheckInLinkMail;
use App\Mail\SelfCheckInWelcomeMail;
use App\Models\Apartment;
use App\Models\GuestCheckIn;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SelfCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_signed_form_and_submit_an_id(): void
    {
        Mail::fake();
        Storage::fake('local');

        $invoice = $this->createInvoice();
        $url = URL::signedRoute('reservations.self-check-in', $invoice);

        $this->get($url)
            ->assertOk()
            ->assertSee('Complete your self check-in')
            ->assertSee('Ada')
            ->assertSee('Lovelace')
            ->assertSee('guest@example.com')
            ->assertSee('Upload your ID');

        $response = $this->post($url, [
            'identity_document' => UploadedFile::fake()->image('passport.jpg'),
            'first_name' => 'Tampered',
            'email' => 'attacker@example.com',
        ]);

        $response->assertRedirect($url)
            ->assertSessionHas('checkin_success');

        $checkIn = GuestCheckIn::query()->firstOrFail();

        $this->assertSame($invoice->id, $checkIn->invoice_id);
        Storage::disk('local')->assertExists($checkIn->document_path);
        Storage::disk('local')->assertExists($checkIn->pdf_path);

        Mail::assertSent(SelfCheckInSubmissionMail::class, fn (SelfCheckInSubmissionMail $mail) =>
            $mail->hasTo('reservations@maisonberesidences.com')
            && $mail->hasBcc('md@maisonberesidences.com')
            && $mail->details['first_name'] === 'Ada'
        );
        Mail::assertSent(SelfCheckInWelcomeMail::class, fn (SelfCheckInWelcomeMail $mail) =>
            $mail->hasTo('guest@example.com')
            && $mail->hasBcc('reservations@maisonberesidences.com')
            && $mail->hasBcc('md@maisonberesidences.com')
        );
    }

    public function test_unsigned_self_check_in_link_is_rejected(): void
    {
        $invoice = $this->createInvoice();

        $this->get(route('reservations.self-check-in', $invoice))->assertForbidden();
    }

    public function test_guest_cannot_submit_self_check_in_twice(): void
    {
        Mail::fake();
        Storage::fake('local');

        $invoice = $this->createInvoice();
        $url = URL::signedRoute('reservations.self-check-in', $invoice);

        $this->post($url, [
            'identity_document' => UploadedFile::fake()->image('passport.jpg'),
        ])->assertSessionHas('checkin_success');

        $this->post($url, [
            'identity_document' => UploadedFile::fake()->image('different-id.jpg'),
        ])->assertSessionHas('checkin_success');

        $this->assertSame(1, GuestCheckIn::query()->count());
        Mail::assertSent(SelfCheckInSubmissionMail::class, 1);
        Mail::assertSent(SelfCheckInWelcomeMail::class, 1);
    }

    public function test_guest_can_resend_emails_for_an_existing_check_in(): void
    {
        Mail::fake();
        Storage::fake('local');

        $invoice = $this->createInvoice();
        $checkIn = GuestCheckIn::create([
            'invoice_id' => $invoice->id,
            'document_disk' => 'local',
            'document_path' => 'self-check-ins/id/passport.jpg',
            'document_original_name' => 'passport.jpg',
            'document_mime' => 'image/jpeg',
            'pdf_path' => 'self-check-ins/id/check-in.pdf',
            'submitted_at' => now(),
        ]);
        Storage::disk('local')->put($checkIn->document_path, 'identity-document');
        Storage::disk('local')->put($checkIn->pdf_path, 'check-in-pdf');

        $url = URL::signedRoute('reservations.self-check-in.resend', $invoice);

        $this->post($url)
            ->assertRedirect()
            ->assertSessionHas('mail_success');

        Mail::assertSent(SelfCheckInSubmissionMail::class, 1);
        Mail::assertSent(SelfCheckInWelcomeMail::class, 1);
    }

    public function test_admin_can_resend_a_fresh_self_check_in_link_from_a_reservation(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $invoice = $this->createInvoice();

        $this->actingAs($admin)
            ->post(route('admin.reservations.resend-self-check-in', $invoice))
            ->assertRedirect()
            ->assertSessionHas('status', 'A fresh self check-in link was sent to guest@example.com.');

        Mail::assertSent(SelfCheckInLinkMail::class, fn (SelfCheckInLinkMail $mail) =>
            $mail->hasTo('guest@example.com')
            && $mail->hasBcc('reservations@maisonberesidences.com')
            && $mail->hasBcc('md@maisonberesidences.com')
            && str_contains($mail->selfCheckInUrl, '/self-check-in')
        );
    }

    private function createInvoice(): Invoice
    {
        $apartment = Apartment::create([
            'name' => 'Stanmore',
            'slug' => 'stanmore',
            'price' => 500,
        ]);
        $invoice = Invoice::create([
            'invoice' => 'MBR-CHECKIN-001',
            'full_name' => 'Ada Lovelace',
            'email' => 'guest@example.com',
            'phone' => '+2349065007079',
            'currency' => '$',
            'currency_code' => 'USD',
            'exchange_rate' => 1,
            'subtotal' => 500,
            'discount' => 0,
            'vat_rate' => 7.5,
            'vat_amount' => 37.5,
            'total' => 537.5,
            'payment_status' => 'paid',
            'payment_payload' => [
                'booking' => [
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                ],
            ],
        ]);
        $invoice->invoiceItems()->create([
            'apartment_id' => $apartment->id,
            'name' => $apartment->name,
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
            'checkin' => now()->addDays(10)->toDateString(),
            'checkout' => now()->addDays(12)->toDateString(),
        ]);

        return $invoice;
    }
}
