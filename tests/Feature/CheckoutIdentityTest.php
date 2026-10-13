<?php

namespace Tests\Feature;

use App\Jobs\SendReservationIdentityDocument;
use App\Mail\ReservationIdentityDocumentMail;
use App\Mail\ReservationReceiptMail;
use App\Models\Apartment;
use App\Models\CheckoutIdentityDocument;
use App\Models\Invoice;
use App\Services\CheckoutIdentityService;
use App\Services\CloudbedsApiService;
use App\Services\PaystackBookingService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CheckoutIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudbeds.api.key' => null, 'services.paystack.public_key' => 'pk_test_identity']);
        Cache::put('currency.usd-ngn', 1500);
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
        Http::preventStrayRequests();
        $this->partialMock(CloudbedsApiService::class, function ($mock): void {
            $mock->shouldReceive('availableRoomTypeForApartment')->andReturn(['id' => 'test-room']);
        });
    }

    public function test_checkout_form_requires_an_id_and_uses_multipart_uploads(): void
    {
        $apartment = $this->apartment();
        $this->get(route('reservations.create', $apartment).'?checkin=2027-03-10&checkout=2027-03-12')
            ->assertOk()->assertSee('Upload your ID (required)')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="identity_document"', false)->assertSee('required data-checkout-id', false);
    }

    public function test_the_legacy_book_entry_point_cannot_bypass_the_website_id_requirement(): void
    {
        $this->get('/book?checkin=2027-03-10&checkout=2027-03-12&adults=2&currency=USD')
            ->assertRedirect(route('apartments.index', [
                'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'currency' => 'USD', 'guests' => 2,
            ]))->assertDontSee('loader.js');
        Http::assertNothingSent();
    }

    public function test_no_payment_can_be_started_without_uploading_an_id(): void
    {
        $this->checkout($this->apartment())->assertUnprocessable()->assertJsonValidationErrors('identity_document')
            ->assertJsonMissingPath('payment');
        $this->assertDatabaseCount('checkout_identity_documents', 0);
        $this->assertDatabaseCount('invoices', 0);
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_unsupported_spoofed_and_oversized_files_are_rejected(): void
    {
        $apartment = $this->apartment();
        foreach ([
            UploadedFile::fake()->create('script.html', 1, 'text/html'),
            UploadedFile::fake()->create('fake-id.jpg', 1, 'text/plain'),
            UploadedFile::fake()->image('large-id.jpg')->size(8193),
        ] as $file) {
            $this->checkout($apartment, ['identity_document' => $file])->assertUnprocessable()
                ->assertJsonValidationErrors('identity_document')->assertJsonMissingPath('payment');
        }
        $this->assertDatabaseCount('checkout_identity_documents', 0);
    }

    public function test_id_is_stored_privately_and_never_put_in_gateway_metadata_or_sent_before_payment(): void
    {
        $response = $this->checkout($this->apartment(), ['identity_document' => UploadedFile::fake()->image('personal-passport.jpg')])->assertOk();
        $document = CheckoutIdentityDocument::firstOrFail();
        $this->assertSame($response->json('payment.reference'), $document->payment_reference);
        $this->assertNull($document->invoice_id);
        $this->assertNull($document->sent_at);
        $this->assertSame('local', $document->document_disk);
        Storage::disk('local')->assertExists($document->document_path);
        $this->assertSame('private', Storage::disk('local')->getVisibility($document->document_path));
        Storage::disk('public')->assertMissing($document->document_path);
        $this->assertStringNotContainsString('checkout-identities', $response->getContent());
        $this->assertStringNotContainsString('personal-passport', $response->getContent());
        $this->assertStringNotContainsString('document_path', $response->getContent());
        $this->assertArrayNotHasKey('document_path', $document->toArray());
        Mail::assertNothingSent();
    }

    public function test_png_and_pdf_ids_are_accepted(): void
    {
        $apartment = $this->apartment();
        foreach ([UploadedFile::fake()->image('id.png'), UploadedFile::fake()->createWithContent('id.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF")] as $file) {
            $this->checkout($apartment, ['identity_document' => $file])->assertOk();
        }
        $this->assertDatabaseCount('checkout_identity_documents', 2);
    }

    public function test_upload_storage_failure_prevents_the_payment_window(): void
    {
        $this->partialMock(CheckoutIdentityService::class, function ($mock): void {
            $mock->shouldReceive('storeForPayment')->once()->andThrow(new RuntimeException('Your ID could not be saved securely. Please upload it again.'));
        });
        $this->checkout($this->apartment(), ['identity_document' => UploadedFile::fake()->image('id.jpg')])
            ->assertUnprocessable()->assertJsonMissingPath('payment');
    }

    public function test_confirmed_booking_sends_bcc_receipt_and_one_separate_id_only_to_reservations(): void
    {
        $payment = $this->readyPayment();
        $this->fakeVerification($payment);
        $invoice = app(PaystackBookingService::class)->processReference($payment['reference']);
        $document = CheckoutIdentityDocument::firstOrFail();
        $this->assertSame($invoice->id, $document->invoice_id);
        $this->assertNotNull($document->sent_at);
        Mail::assertSent(ReservationReceiptMail::class, function ($mail): bool {
            $mail->build();

            return $mail->hasTo('guest@example.com') && $mail->hasBcc('concierge@maisonberesidences.com')
                && $mail->hasBcc('reservations@maisonberesidences.com')
                && $mail->attachments === [] && $mail->diskAttachments === [];
        });
        Mail::assertSent(ReservationIdentityDocumentMail::class, function ($mail) use ($document): bool {
            $mail->build();

            return $mail->hasTo('reservations@maisonberesidences.com') && count($mail->to) === 1 && $mail->bcc === [] && $mail->cc === []
                && $mail->attachments === [] && count($mail->diskAttachments) === 1
                && $mail->diskAttachments[0]['path'] === $document->document_path
                && $mail->diskAttachments[0]['name'] === 'guest-id.jpg';
        });
        Mail::assertSentCount(2);
    }

    public function test_duplicate_confirmations_do_not_resend_the_id_or_receipt(): void
    {
        $payment = $this->readyPayment();
        $this->fakeVerification($payment, 2);
        $service = app(PaystackBookingService::class);
        $service->processReference($payment['reference']);
        $service->processReference($payment['reference']);
        $this->assertDatabaseCount('invoices', 1);
        $document = CheckoutIdentityDocument::firstOrFail();
        $this->assertSame($document->invoice_id, app(CheckoutIdentityService::class)->requireForPayment($payment['reference'], $document->apartment_id)->invoice_id);
        Mail::assertSent(ReservationReceiptMail::class, 1);
        Mail::assertSent(ReservationIdentityDocumentMail::class, 1);
    }

    public function test_gateway_metadata_alone_cannot_bypass_the_server_side_id_requirement(): void
    {
        $payment = $this->readyPayment();
        CheckoutIdentityDocument::firstOrFail()->delete();
        $this->assertPaymentRejected($payment);
    }

    public function test_a_missing_private_file_prevents_an_id_less_booking(): void
    {
        $payment = $this->readyPayment();
        Storage::disk('local')->delete(CheckoutIdentityDocument::firstOrFail()->document_path);
        $this->assertPaymentRejected($payment);
    }

    public function test_another_apartment_cannot_use_the_uploaded_ids_payment_reference(): void
    {
        $payment = $this->readyPayment();
        $payment['metadata']['booking']['apartment_id'] = $this->apartment('Other apartment')->id;
        $this->assertPaymentRejected($payment);
    }

    public function test_failed_database_write_removes_only_the_new_upload_not_the_existing_id(): void
    {
        $apartment = $this->apartment();
        $service = app(CheckoutIdentityService::class);
        $document = $service->storeForPayment('one-reference', $apartment, UploadedFile::fake()->image('id.jpg'));
        try {
            $service->storeForPayment('one-reference', $apartment, UploadedFile::fake()->image('id.jpg'));
            $this->fail('Duplicate references must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('saved securely', $exception->getMessage());
        }
        $this->assertDatabaseCount('checkout_identity_documents', 1);
        Storage::disk('local')->assertExists($document->document_path);
        $this->assertCount(1, Storage::disk('local')->allFiles('checkout-identities'));
    }

    public function test_id_email_failure_keeps_the_document_retryable_without_resending_a_receipt(): void
    {
        $payment = $this->readyPayment();
        $this->fakeVerification($payment);
        $invoice = app(PaystackBookingService::class)->processReference($payment['reference']);
        $document = CheckoutIdentityDocument::firstOrFail();
        $document->update(['sent_at' => null]);
        $pending = Mockery::mock(PendingMail::class);
        $attempts = 0;
        $pending->shouldReceive('send')->twice()->andReturnUsing(function ($mail) use (&$attempts): void {
            $this->assertInstanceOf(ReservationIdentityDocumentMail::class, $mail);
            if (++$attempts === 1) {
                throw new RuntimeException('Temporary transport failure');
            }
        });
        Mail::shouldReceive('to')->twice()->with('reservations@maisonberesidences.com')->andReturn($pending);
        $service = app(CheckoutIdentityService::class);
        try {
            $service->sendForInvoice($invoice);
            $this->fail('The first mail attempt should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary transport failure', $exception->getMessage());
        }
        $this->assertNull($document->fresh()->sent_at);
        $service->sendForInvoice($invoice);
        $this->assertNotNull($document->fresh()->sent_at);
    }

    public function test_a_temporary_id_email_failure_queues_a_retry_and_keeps_the_paid_booking_confirmed(): void
    {
        Queue::fake([SendReservationIdentityDocument::class]);
        $payment = $this->readyPayment();
        $this->fakeVerification($payment);
        $this->partialMock(CheckoutIdentityService::class, function ($mock): void {
            $mock->shouldReceive('sendForInvoice')->once()->andThrow(new RuntimeException('Mail unavailable'));
        });
        $invoice = app(PaystackBookingService::class)->processReference($payment['reference']);
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertNull(CheckoutIdentityDocument::firstOrFail()->sent_at);
        Mail::assertSent(ReservationReceiptMail::class, 1);
        Queue::assertPushed(SendReservationIdentityDocument::class, fn ($job): bool => $job->invoiceId === $invoice->id);
    }

    public function test_concierge_bcc_is_defined_on_every_receipt_even_outside_the_payment_sender(): void
    {
        $mail = new ReservationReceiptMail(new Invoice(['invoice' => 'PREVIEW']));
        $this->assertTrue($mail->hasBcc('concierge@maisonberesidences.com'));
    }

    private function assertPaymentRejected(array $payment): void
    {
        $this->fakeVerification($payment);
        try {
            app(PaystackBookingService::class)->processReference($payment['reference']);
            $this->fail('A payment without its matching ID must not create a booking.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('uploaded ID is required', $exception->getMessage());
        }
        $this->assertDatabaseCount('invoices', 0);
        Mail::assertNothingSent();
    }

    private function fakeVerification(array $payment, int $times = 1): void
    {
        $this->mock(PaystackService::class, function ($mock) use ($payment, $times): void {
            $mock->shouldReceive('verify')->times($times)->with($payment['reference'])->andReturn([
                'status' => 'success', 'amount' => $payment['amount'], 'currency' => $payment['currency'],
                'metadata' => $payment['metadata'],
            ]);
        });
    }

    private function readyPayment(): array
    {
        return $this->checkout($this->apartment(), ['identity_document' => UploadedFile::fake()->image('id.jpg')])->assertOk()->json('payment');
    }

    private function checkout(Apartment $apartment, array $overrides = []): TestResponse
    {
        return $this->withHeader('Accept', 'application/json')->post(route('reservations.store', $apartment), array_replace([
            'checkin' => '2027-03-10', 'checkout' => '2027-03-12', 'guests' => 1,
            'first_name' => 'Test', 'last_name' => 'Guest', 'email' => 'guest@example.com',
            'phone_country' => 'NG', 'phone' => '8012345678',
        ], $overrides));
    }

    private function apartment(string $name = 'Beaufort'): Apartment
    {
        return Apartment::create(['name' => $name, 'slug' => str($name)->slug(), 'price' => 500, 'max_adults' => 7, 'allow' => true]);
    }
}
