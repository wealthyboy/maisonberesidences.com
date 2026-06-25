<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Services\PaystackService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaystackService $paystack) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! $this->paystack->webhookIsValid($payload, $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = $request->json()->all();

        if (data_get($event, 'event') !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = (string) data_get($event, 'data.reference');

        try {
            $payment = $this->paystack->verify($reference);
            $invoice = Invoice::query()->where('payment_reference', $reference)->first();
            $booking = $invoice ? null : $this->bookingFromPayment($payment);

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
                Log::warning('Rejected Paystack payment verification.', ['reference' => $reference]);

                return response()->json(['message' => 'Payment verification failed.'], 422);
            }

            DB::transaction(function () use ($invoice, $booking, $payment, $event, $reference): void {
                if ($invoice) {
                    $invoice->refresh();

                    if ($invoice->payment_status === 'paid') {
                        return;
                    }

                    $invoice->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'payment_payload' => [
                            'webhook' => $event,
                            'verified' => $payment,
                        ],
                    ]);

                    return;
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
            });
        } catch (\Throwable $exception) {
            Log::error('Paystack webhook processing failed.', ['reference' => $reference, 'message' => $exception->getMessage()]);

            return response()->json(['message' => 'Could not process payment.'], 422);
        }

        return response()->json(['status' => 'ok']);
    }

    private function bookingFromPayment(array $payment): array
    {
        $metadata = data_get($payment, 'metadata', []);

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $booking = data_get($metadata, 'booking');

        if (is_string($booking)) {
            $booking = json_decode($booking, true) ?: [];
        }

        if (is_array($booking) && $booking !== []) {
            return $booking;
        }

        foreach ((array) data_get($metadata, 'custom_fields', []) as $field) {
            if (data_get($field, 'variable_name') !== 'booking_payload') {
                continue;
            }

            $booking = json_decode((string) data_get($field, 'value'), true);

            if (is_array($booking) && $booking !== []) {
                return $booking;
            }
        }

        throw new \RuntimeException('Paystack payload does not include booking details.');
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
