<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Services\ApartmentQuoteService;
use App\Services\CouponService;
use App\Services\CurrencyService;
use App\Services\PaystackBookingService;
use App\Services\PaystackService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ApartmentQuoteService $quotes,
        private readonly PaystackService $paystack,
        private readonly CouponService $coupons,
        private readonly CurrencyService $currencies,
        private readonly PaystackBookingService $paystackBookings,
    ) {}

    public function create(Request $request, Apartment $apartment): View|RedirectResponse
    {
        $stay = $this->stay($request);

        if (! $stay) {
            return redirect()->route('apartments.index')->with('booking_error', 'Choose check-in and check-out dates before reserving a residence.');
        }

        $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));

        return view('reservations.create', compact('apartment', 'stay', 'quote'));
    }

    public function store(Request $request, Apartment $apartment): JsonResponse|RedirectResponse
    {
        $stay = $this->stay($request);

        if (! $stay && $request->expectsJson()) {
            return response()->json(['message' => 'Choose valid check-in and check-out dates.'], 422);
        }

        if (! $stay) {
            return back()->withErrors(['stay' => 'Choose valid check-in and check-out dates.'])->withInput();
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:190'],
            'country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        $data['phone'] = trim($data['country_code'].' '.ltrim($data['phone'], '0 '));

        $isUnavailable = $apartment->invoiceItems()
            ->whereHas('invoice', fn ($invoice) => $invoice->where('payment_status', 'paid'))
            ->where('checkin', '<', $stay['checkout'])
            ->where('checkout', '>', $stay['checkin'])
            ->exists();

        if ($isUnavailable) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'That residence was just reserved for part of your selected stay. Please choose another residence.'], 422);
            }

            return redirect()->route('apartments.index', $request->only('checkin', 'checkout'))
                ->with('booking_error', 'That residence was just reserved for part of your selected stay. Please choose another residence.');
        }

        $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));

        try {
            $coupon = $this->coupons->apply($data['coupon_code'] ?? null, $quote['total'], $quote['currency']);
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['coupon_code' => $exception->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            try {
                $payment = $this->inlinePaymentPayload($apartment, $stay, $quote, $coupon, $data);
            } catch (\Throwable $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Open Paystack to complete payment.',
                'payment' => $payment,
                'receipt_url' => route('reservations.payment-return', ['reference' => $payment['reference']]),
            ]);
        }

        return back()->withErrors(['payment' => 'Please use the Make payment button to complete this reservation securely.'])->withInput();
    }

    public function coupon(Request $request, Apartment $apartment): JsonResponse
    {
        $stay = $this->stay($request);

        if (! $stay) {
            return response()->json(['message' => 'Choose valid check-in and check-out dates first.'], 422);
        }

        $data = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));
            $coupon = $this->coupons->apply($data['coupon_code'] ?? null, $quote['total'], $quote['currency']);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'coupon' => $coupon['code'],
            'discount' => $coupon['discount'],
            'total' => $coupon['total'],
            'display_discount' => $coupon['display_discount'],
            'display_total' => $coupon['display_total'],
            'message' => $coupon['code'] ? 'Coupon applied.' : 'Coupon removed.',
        ]);
    }

    public function receipt(Invoice $invoice): View
    {
        return view('reservations.receipt', ['invoice' => $invoice->load('invoiceItems.apartment.property', 'invoiceItems.apartment.images')]);
    }

    public function receiptByReference(Request $request): View|RedirectResponse
    {
        $reference = (string) $request->query('reference');
        $invoice = Invoice::query()->where('payment_reference', $reference)->first();

        if (! $invoice) {
            return redirect()->route('apartments.index')
                ->with('booking_error', 'Your payment was received. Please allow a moment for your receipt to become available.');
        }

        return redirect()->route('reservations.receipt', $invoice);
    }

    public function paymentReturn(Request $request): RedirectResponse
    {
        $invoice = Invoice::query()
            ->where('payment_reference', (string) $request->query('reference'))
            ->first();

        if ($invoice) {
            return redirect()->route('reservations.receipt', $invoice);
        }

        return redirect()->route('apartments.index')
            ->with('booking_error', 'Payment received. We are waiting for Paystack to confirm your booking.');
    }

    public function confirmPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
            'response' => ['nullable', 'array'],
        ]);

        try {
            $invoice = $this->paystackBookings->processReference($data['reference'], [
                'event' => 'popup.callback',
                'data' => [
                    'reference' => $data['reference'],
                    'response' => $data['response'] ?? [],
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Booking confirmed.',
            'reference' => $invoice->payment_reference,
            'invoice' => $invoice->invoice,
            'receipt_url' => route('reservations.receipt', $invoice),
        ]);
    }

    private function stay(Request $request): ?array
    {
        $validated = $request->validate([
            'checkin' => ['nullable', 'date'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
        ]);

        if (! filled($validated['checkin'] ?? null) || ! filled($validated['checkout'] ?? null)) {
            return null;
        }

        return [
            'checkin' => Carbon::parse($validated['checkin'])->startOfDay(),
            'checkout' => Carbon::parse($validated['checkout'])->startOfDay(),
        ];
    }

    private function nextInvoiceNumber(): string
    {
        do {
            $number = 'MBR-'.now()->format('ymd').'-'.strtoupper(str()->random(6));
        } while (Invoice::query()->where('invoice', $number)->exists());

        return $number;
    }

    private function inlinePaymentPayload(Apartment $apartment, array $stay, array $quote, array $coupon, array $data): array
    {
        $reference = $this->nextPaymentReference();
        $invoiceNumber = $this->nextInvoiceNumber();
        $paymentQuote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $this->currencies->paystackCurrency());
        $paymentCoupon = $this->coupons->apply($coupon['code'], $paymentQuote['total'], $paymentQuote['currency']);
        $booking = [
            'invoice_number' => $invoiceNumber,
            'reference' => $reference,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'full_name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'] ?? null,
            'display_currency' => $quote['currency']['code'],
            'display_currency_symbol' => $quote['currency']['symbol'],
            'display_exchange_rate' => (float) $quote['currency']['rate'],
            'display_subtotal' => (float) $quote['total'],
            'display_discount' => (float) $coupon['discount'],
            'display_total' => (float) $coupon['total'],
            'currency' => $paymentQuote['currency']['code'],
            'currency_symbol' => $paymentQuote['currency']['symbol'],
            'exchange_rate' => (float) $paymentQuote['currency']['rate'],
            'length_of_stay' => $quote['nights'],
            'subtotal' => (float) $paymentQuote['total'],
            'discount' => (float) $paymentCoupon['discount'],
            'discount_type' => $paymentCoupon['type'],
            'coupon' => $paymentCoupon['code'],
            'total' => (float) $paymentCoupon['total'],
            'original_amount' => (float) $paymentQuote['total'],
            'payment_currency' => $paymentQuote['currency']['code'],
            'payment_total' => (float) $paymentCoupon['total'],
            'from' => $stay['checkin']->toDateString(),
            'to' => $stay['checkout']->toDateString(),
            'apartment_id' => $apartment->id,
            'apartment_name' => $apartment->name,
            'page_url' => url()->previous() ?: route('reservations.create', [
                'apartment' => $apartment,
                'checkin' => $stay['checkin']->toDateString(),
                'checkout' => $stay['checkout']->toDateString(),
            ]),
            'receipt_url' => route('reservations.payment-return', ['reference' => $reference]),
            'public_receipt_url' => route('reservations.receipt-reference', ['reference' => $reference]),
        ];

        $metadata = [
            'invoice_number' => $invoiceNumber,
            'coupon_code' => $paymentCoupon['code'],
            'discount' => (float) $paymentCoupon['discount'],
            'booking' => $booking,
            'booking_json' => json_encode($booking),
            'custom_fields' => [
                [
                    'display_name' => 'Booking payload',
                    'variable_name' => 'booking_payload',
                    'value' => json_encode($booking),
                ],
            ],
        ];

        return [
            'key' => $this->paystack->publicKey(),
            'email' => $data['email'],
            'amount' => (int) round((float) $paymentCoupon['total'] * 100),
            'currency' => $paymentQuote['currency']['code'],
            'reference' => $reference,
            'receipt_url' => route('reservations.receipt-reference', ['reference' => $reference]),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'metadata' => $metadata,
        ];
    }

    private function nextPaymentReference(): string
    {
        do {
            $reference = 'MBR-'.str()->upper(str()->random(14));
        } while (Invoice::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }
}
