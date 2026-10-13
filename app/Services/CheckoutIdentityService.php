<?php

namespace App\Services;

use App\Mail\ReservationIdentityDocumentMail;
use App\Models\Apartment;
use App\Models\CheckoutIdentityDocument;
use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckoutIdentityService
{
    public function storeForPayment(string $reference, Apartment $apartment, UploadedFile $file): CheckoutIdentityDocument
    {
        $mime = (string) $file->getMimeType();
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'][$mime] ?? null;
        if (! $extension) {
            throw new RuntimeException('Upload a JPG, PNG or PDF identity document.');
        }
        $disk = 'local';
        $path = 'checkout-identities/'.Str::uuid().'.'.$extension;

        try {
            $stored = $file->storeAs('checkout-identities', basename($path), ['disk' => $disk, 'visibility' => 'private']);
            if (! $stored || ! Storage::disk($disk)->exists($path)) {
                throw new RuntimeException('The ID upload was not saved.');
            }

            return CheckoutIdentityDocument::create([
                'payment_reference' => $reference, 'apartment_id' => $apartment->id,
                'document_disk' => $disk, 'document_path' => $path, 'document_mime' => $mime,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            Log::error('Checkout ID could not be saved.', ['apartment_id' => $apartment->id]);
            throw new RuntimeException('Your ID could not be saved securely. Please upload it again.', previous: $exception);
        }
    }

    public function requireForPayment(string $reference, int $apartmentId): CheckoutIdentityDocument
    {
        $document = CheckoutIdentityDocument::query()->where('payment_reference', $reference)->lockForUpdate()->first();
        if (! $document || (int) $document->apartment_id !== $apartmentId
            || ! Storage::disk($document->document_disk)->exists($document->document_path)) {
            throw new RuntimeException('An uploaded ID is required for this booking. Contact reservations if payment has already been made.');
        }

        return $document;
    }

    public function sendForInvoice(Invoice $invoice): void
    {
        // Separate from the receipt. Lock the upload so duplicate callbacks and
        // queued retries do not resend an ID that was already accepted by mail.
        if ($invoice->payment_status !== 'paid') {
            return;
        }
        DB::transaction(function () use ($invoice): void {
            $document = CheckoutIdentityDocument::query()->where('invoice_id', $invoice->id)->lockForUpdate()->first();
            if (! $document || $document->sent_at) {
                return;
            }
            $document->setRelation('invoice', $invoice);
            Mail::to('reservations@maisonberesidences.com')->send(new ReservationIdentityDocumentMail($document));
            $document->update(['sent_at' => now()]);
        });
    }
}
