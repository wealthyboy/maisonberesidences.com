<?php

namespace App\Services;

use App\Mail\ReservationReceiptMail;
use App\Models\Apartment;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaystackBookingService
{
    public function __construct(private readonly PaystackService $paystack) {}

    public function processReference(string $reference, array $event = []): Invoice
    {
        $payment = $this->paystack->verify($reference);
        $invoice = Invoice::query()->where('payment_reference', $reference)->first();
        $booking = $invoice ? null : $this->bookingFromPayment($payment, $event);

        $expectedAmount = $invoice
            ? (int) round((float) $invoice->total * 100)
            : (int) round((float) data_get($booking, 'total') * 100);
        $expectedCurrency = $invoice
            ? $invoice->currency_code
            : strtoupper((string) data_get($booking, 'currency'));

        if (
            data_get($payment, 'status') !== 'success'
            || (int) data_get($payment, 'amount') !== $expectedAmount
            || strtoupper((string) data_get($payment, 'currency')) !== $expectedCurrency
        ) {
            Log::warning('Rejected Paystack payment verification.', [
                'reference' => $reference,
                'payment_status' => data_get($payment, 'status'),
                'payment_amount' => data_get($payment, 'amount'),
                'expected_amount' => $expectedAmount,
                'payment_currency' => data_get($payment, 'currency'),
                'expected_currency' => $expectedCurrency,
            ]);

            throw new \RuntimeException('Payment verification failed.');
        }

        $invoice = DB::transaction(function () use ($invoice, $booking, $payment, $event, $reference): Invoice {
            if ($invoice) {
                $invoice->refresh();

                if ($invoice->payment_status !== 'paid') {
                    $invoice->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'payment_payload' => [
                            'webhook' => $event,
                            'verified' => $payment,
                        ],
                    ]);
                }

                Log::info('Paystack booking matched existing invoice.', [
                    'reference' => $reference,
                    'invoice_id' => $invoice->id,
                    'invoice' => $invoice->invoice,
                    'payment_status' => $invoice->payment_status,
                ]);

                return $invoice;
            }

            $apartment = Apartment::query()->findOrFail((int) data_get($booking, 'apartment_id'));
            $checkin = Carbon::parse((string) data_get($booking, 'from'))->startOfDay();
            $checkout = Carbon::parse((string) data_get($booking, 'to'))->startOfDay();

            $isUnavailable = $apartment->invoiceItems()
                ->whereHas('invoice', fn ($invoice) => $invoice->where('payment_status', 'paid'))
                ->where('checkin', '<', $checkout)
                ->where('checkout', '>', $checkin)
                ->exists();

            if ($isUnavailable) {
                Log::warning('Paid Paystack booking overlaps an existing reservation.', [
                    'reference' => $reference,
                    'apartment_id' => $apartment->id,
                ]);

                throw new \RuntimeException('Paid booking overlaps an existing reservation.');
            }

            $invoice = Invoice::create([
                'invoice' => $this->availableInvoiceNumber((string) data_get($booking, 'invoice_number')),
                'full_name' => (string) data_get($booking, 'full_name'),
                'email' => (string) data_get($booking, 'email'),
                'phone' => (string) data_get($booking, 'phone'),
                'country' => data_get($booking, 'country'),
                'currency' => (string) data_get($booking, 'currency_symbol'),
                'currency_code' => strtoupper((string) data_get($booking, 'currency')),
                'exchange_rate' => (float) data_get($booking, 'exchange_rate', 1),
                'subtotal' => (float) data_get($booking, 'subtotal'),
                'discount' => (float) data_get($booking, 'discount'),
                'discount_type' => data_get($booking, 'discount_type'),
                'coupon_code' => data_get($booking, 'coupon'),
                'total' => (float) data_get($booking, 'total'),
                'description' => filled(data_get($booking, 'coupon')) ? 'Coupon '.data_get($booking, 'coupon').' applied.' : null,
                'payment_status' => 'paid',
                'payment_reference' => $reference,
                'paid_at' => now(),
                'payment_payload' => [
                    'booking' => $booking,
                    'webhook' => $event,
                    'verified' => $payment,
                ],
            ]);

            $nights = max(1, (int) data_get($booking, 'length_of_stay', $checkin->diffInDays($checkout)));

            $invoice->invoiceItems()->create([
                'apartment_id' => $apartment->id,
                'name' => (string) data_get($booking, 'apartment_name', $apartment->name),
                'quantity' => $nights,
                'price' => round((float) data_get($booking, 'subtotal') / $nights, 2),
                'total' => (float) data_get($booking, 'subtotal'),
                'checkin' => $checkin,
                'checkout' => $checkout,
            ]);

            Log::info('Paystack booking invoice created.', [
                'reference' => $reference,
                'invoice_id' => $invoice->id,
                'invoice' => $invoice->invoice,
                'apartment_id' => $apartment->id,
                'checkin' => $checkin->toDateString(),
                'checkout' => $checkout->toDateString(),
            ]);

            return $invoice;
        });

        $this->sendReceipt($invoice);

        return $invoice->fresh('invoiceItems.apartment.property');
    }

    private function sendReceipt(Invoice $invoice): void
    {
        if ($invoice->sent || ! filled($invoice->email)) {
            return;
        }

        try {
            Mail::to($invoice->email)
                ->bcc('info@maisonberesidences.com')
                ->send(new ReservationReceiptMail($invoice->loadMissing('invoiceItems.apartment.property')));
            $invoice->forceFill(['sent' => true])->save();

            Log::info('Reservation receipt email sent.', [
                'invoice_id' => $invoice->id,
                'email' => $invoice->email,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Reservation receipt email failed.', [
                'invoice_id' => $invoice->id,
                'email' => $invoice->email,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function bookingFromPayment(array $payment, array $event = []): array
    {
        foreach ([$payment, data_get($event, 'data', [])] as $source) {
            $booking = $this->bookingFromMetadata(data_get($source, 'metadata', []));

            if ($booking !== []) {
                return $booking;
            }
        }

        Log::warning('Paystack payload does not include booking details.', [
            'payment_metadata' => data_get($payment, 'metadata'),
            'event_metadata' => data_get($event, 'data.metadata'),
        ]);

        throw new \RuntimeException('Paystack payload does not include booking details.');
    }

    private function bookingFromMetadata(mixed $metadata): array
    {
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        if (! is_array($metadata)) {
            return [];
        }

        $booking = data_get($metadata, 'booking');

        if ($this->isBooking($booking)) {
            return $booking;
        }

        if (is_string($booking)) {
            $decoded = json_decode($booking, true) ?: [];

            if ($this->isBooking($decoded)) {
                return $decoded;
            }
        }

        $bookingJson = data_get($metadata, 'booking_json');

        if (is_string($bookingJson)) {
            $decoded = json_decode($bookingJson, true) ?: [];

            if ($this->isBooking($decoded)) {
                return $decoded;
            }
        }

        foreach ((array) data_get($metadata, 'custom_fields', []) as $field) {
            $booking = data_get($field, 'booking');

            if ($this->isBooking($booking)) {
                return $booking;
            }

            $value = data_get($field, 'value');
            $name = (string) data_get($field, 'variable_name');

            if (! in_array($name, ['booking_payload', 'booking'], true)) {
                continue;
            }

            $decoded = is_string($value) ? json_decode($value, true) : $value;

            if ($this->isBooking($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function isBooking(mixed $booking): bool
    {
        return is_array($booking)
            && filled(data_get($booking, 'apartment_id'))
            && filled(data_get($booking, 'from'))
            && filled(data_get($booking, 'to'))
            && filled(data_get($booking, 'total'))
            && filled(data_get($booking, 'currency'));
    }

    private function availableInvoiceNumber(string $preferred): string
    {
        if ($preferred !== '' && ! Invoice::query()->where('invoice', $preferred)->exists()) {
            return $preferred;
        }

        do {
            $number = 'MBR-'.now()->format('ymd').'-'.strtoupper(str()->random(6));
        } while (Invoice::query()->where('invoice', $number)->exists());

        return $number;
    }
}
