<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Rules\MinimumStay;
use App\Services\AdditionalServiceQuoteService;
use App\Services\ApartmentQuoteService;
use App\Services\CouponService;
use App\Services\CurrencyService;
use App\Services\PaystackBookingService;
use App\Services\PaystackService;
use App\Services\VatService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ApartmentQuoteService $quotes,
        private readonly AdditionalServiceQuoteService $serviceQuotes,
        private readonly PaystackService $paystack,
        private readonly CouponService $coupons,
        private readonly CurrencyService $currencies,
        private readonly PaystackBookingService $paystackBookings,
        private readonly VatService $vat,
    ) {}

    public function create(Request $request, Apartment $apartment): View|RedirectResponse
    {
        if (! $apartment->allow) {
            return redirect()->route('apartments.show', $apartment)
                ->with('booking_error', 'This apartment is currently unavailable for booking.');
        }

        $stay = $this->stay($request);

        if (! $stay) {
            return redirect()->route('apartments.index')->with('booking_error', 'Choose check-in and check-out dates before reserving a residence.');
        }

        if (! $apartment->isAvailableFor($stay['checkin'], $stay['checkout'])) {
            return redirect()->route('apartments.index', [
                'checkin' => $stay['checkin']->toDateString(),
                'checkout' => $stay['checkout']->toDateString(),
            ])->with('booking_error', 'This apartment is not available for the selected stay.');
        }

        $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));
        $additionalServices = $this->serviceQuotes->availableFor($apartment, $quote['currency']);
        $vat = [
            'rate' => 0.0,
            'amount' => 0.0,
            'display_amount' => $this->currencies->format(0, $quote['currency']),
        ];
        $displayCheckoutTotal = $this->currencies->format($quote['total'], $quote['currency']);

        return view('reservations.create', compact('apartment', 'stay', 'quote', 'additionalServices', 'vat', 'displayCheckoutTotal'));
    }

    public function store(Request $request, Apartment $apartment): JsonResponse|RedirectResponse
    {
        if (! $apartment->allow) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This apartment is currently unavailable for booking.'], 422);
            }

            return redirect()->route('apartments.show', $apartment)
                ->with('booking_error', 'This apartment is currently unavailable for booking.');
        }

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
            'phone_country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:40'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'services' => ['nullable', 'array'],
            'services.*' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $phoneCountries = $this->phoneCountries();
        $phoneCountry = strtoupper((string) $data['phone_country']);

        if (! isset($phoneCountries[$phoneCountry])) {
            return response()->json(['message' => 'Choose a valid phone country code.'], 422);
        }

        $dialCode = $phoneCountries[$phoneCountry]['dial'];
        $dialDigits = ltrim($dialCode, '+');
        $phoneDigits = preg_replace('/\D+/', '', (string) $data['phone']) ?? '';

        // Accept either a local number or a number that already contains the selected dial code.
        if (str_starts_with($phoneDigits, $dialDigits)) {
            $phoneDigits = substr($phoneDigits, strlen($dialDigits));
        }

        $phoneDigits = ltrim($phoneDigits, '0');

        if ($phoneDigits === '') {
            return response()->json(['message' => 'Enter a valid phone number.'], 422);
        }

        $data['phone'] = $dialCode.$phoneDigits;
        $data['country'] = $phoneCountries[$phoneCountry]['country'];

        $isUnavailable = ! $apartment->isAvailableFor($stay['checkin'], $stay['checkout']);

        if ($isUnavailable) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'That residence was just reserved for part of your selected stay. Please choose another residence.'], 422);
            }

            return redirect()->route('apartments.index', $request->only('checkin', 'checkout'))
                ->with('booking_error', 'That residence was just reserved for part of your selected stay. Please choose another residence.');
        }

        $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));
        $servicesQuote = $this->serviceQuotes->quoteSelection($apartment, $data['services'] ?? [], $quote['currency']);

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
                $payment = $this->inlinePaymentPayload($request, $apartment, $stay, $quote, $coupon, $servicesQuote, $data);
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
        if (! $apartment->allow) {
            return response()->json(['message' => 'This apartment is currently unavailable for booking.'], 422);
        }

        $stay = $this->stay($request);

        if (! $stay) {
            return response()->json(['message' => 'Choose valid check-in and check-out dates first.'], 422);
        }

        if (! $apartment->isAvailableFor($stay['checkin'], $stay['checkout'])) {
            return response()->json(['message' => 'This apartment is not available for the selected stay.'], 422);
        }

        $data = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'services' => ['nullable', 'array'],
            'services.*' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $quote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $request->attributes->get('currency'));
            $coupon = $this->coupons->apply($data['coupon_code'] ?? null, $quote['total'], $quote['currency']);
            $servicesQuote = $this->serviceQuotes->quoteSelection($apartment, $data['services'] ?? [], $quote['currency']);
            $vat = [
                'rate' => 0.0,
                'amount' => 0.0,
                'display_amount' => $this->currencies->format(0, $quote['currency']),
            ];
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'coupon' => $coupon['code'],
            'discount' => $coupon['discount'],
            'services_total' => $servicesQuote['subtotal'],
            'display_services_total' => $servicesQuote['display_subtotal'],
            'vat_rate' => $vat['rate'],
            'vat_amount' => $vat['amount'],
            'display_vat' => $vat['display_amount'],
            'total' => round($coupon['total'] + $vat['amount'] + $servicesQuote['subtotal'], 2),
            'display_discount' => $coupon['display_discount'],
            'display_total' => $this->currencies->format($coupon['total'] + $vat['amount'] + $servicesQuote['subtotal'], $quote['currency']),
            'message' => $coupon['code'] ? 'Coupon applied.' : 'Coupon removed.',
        ]);
    }

    public function receipt(Invoice $invoice): View
    {
        return view('reservations.receipt', ['invoice' => $invoice->load('invoiceItems.apartment.property', 'invoiceItems.apartment.images', 'serviceItems')]);
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
            'checkin' => ['nullable', 'date', 'after_or_equal:today'],
            'checkout' => ['nullable', 'date', 'after:checkin', new MinimumStay($request->input('checkin'))],
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

    private function inlinePaymentPayload(Request $request, Apartment $apartment, array $stay, array $quote, array $coupon, array $servicesQuote, array $data): array
    {
        $reference = $this->nextPaymentReference();
        $invoiceNumber = $this->nextInvoiceNumber();
        $paymentQuote = $this->quotes->quote($apartment, $stay['checkin'], $stay['checkout'], $this->currencies->paystackCurrency());
        $paymentCoupon = $this->coupons->apply($coupon['code'], $paymentQuote['total'], $paymentQuote['currency']);
        $serviceQuantities = collect($servicesQuote['items'])
            ->mapWithKeys(fn (array $item) => [$item['additional_service_id'] => $item['quantity']])
            ->all();
        $paymentServices = $this->serviceQuotes->quoteSelection($apartment, $serviceQuantities, $paymentQuote['currency']);
        $paymentVat = [
            'rate' => 0.0,
            'amount' => 0.0,
            'display_amount' => $this->currencies->format(0, $paymentQuote['currency']),
        ];
        $displayVat = [
            'rate' => 0.0,
            'amount' => 0.0,
            'display_amount' => $this->currencies->format(0, $quote['currency']),
        ];
        $paymentSubtotal = round($paymentQuote['total'] + $paymentServices['subtotal'], 2);
        $paymentTotal = round($paymentCoupon['total'] + $paymentVat['amount'] + $paymentServices['subtotal'], 2);
        $isJacobTestPayment = strtolower((string) optional($request->user())->email) === 'jacob.atam@gmail.com';
        $paystackChargeTotal = $isJacobTestPayment ? 100.00 : $paymentTotal;
        $displaySubtotal = round($quote['total'] + $servicesQuote['subtotal'], 2);
        $displayTotal = round($coupon['total'] + $displayVat['amount'] + $servicesQuote['subtotal'], 2);
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
            'display_accommodation_subtotal' => (float) $quote['total'],
            'display_services_subtotal' => (float) $servicesQuote['subtotal'],
            'display_subtotal' => $displaySubtotal,
            'display_discount' => (float) $coupon['discount'],
            'display_vat_rate' => $displayVat['rate'],
            'display_vat_amount' => $displayVat['amount'],
            'display_total' => $displayTotal,
            'currency' => $paymentQuote['currency']['code'],
            'currency_symbol' => $paymentQuote['currency']['symbol'],
            'exchange_rate' => (float) $paymentQuote['currency']['rate'],
            'length_of_stay' => $quote['nights'],
            'accommodation_subtotal' => (float) $paymentQuote['total'],
            'services_subtotal' => (float) $paymentServices['subtotal'],
            'subtotal' => $paymentSubtotal,
            'discount' => (float) $paymentCoupon['discount'],
            'vat_rate' => $paymentVat['rate'],
            'vat_amount' => $paymentVat['amount'],
            'discount_type' => $paymentCoupon['type'],
            'coupon' => $paymentCoupon['code'],
            'total' => $paymentTotal,
            'original_amount' => $paymentSubtotal,
            'payment_currency' => $paymentQuote['currency']['code'],
            'payment_total' => $paystackChargeTotal,
            'actual_booking_total' => $paymentTotal,
            'test_payment_override' => $isJacobTestPayment,
            'from' => $stay['checkin']->toDateString(),
            'to' => $stay['checkout']->toDateString(),
            'apartment_id' => $apartment->id,
            'apartment_name' => $apartment->name,
            'services' => $paymentServices['items'],
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
            'amount' => (int) round($paystackChargeTotal * 100),
            'currency' => $paymentQuote['currency']['code'],
            'reference' => $reference,
            'receipt_url' => route('reservations.receipt-reference', ['reference' => $reference]),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'metadata' => $metadata,
        ];
    }

    private function phoneCountries(): array
    {
        return [
            'NG' => ['dial' => '+234', 'country' => 'Nigeria'],
            'US' => ['dial' => '+1', 'country' => 'United States'],
            'CA' => ['dial' => '+1', 'country' => 'Canada'],
            'GB' => ['dial' => '+44', 'country' => 'United Kingdom'],
            'GH' => ['dial' => '+233', 'country' => 'Ghana'],
            'ZA' => ['dial' => '+27', 'country' => 'South Africa'],
            'AE' => ['dial' => '+971', 'country' => 'United Arab Emirates'],
            'KE' => ['dial' => '+254', 'country' => 'Kenya'],
            'UG' => ['dial' => '+256', 'country' => 'Uganda'],
            'TZ' => ['dial' => '+255', 'country' => 'Tanzania'],
            'RW' => ['dial' => '+250', 'country' => 'Rwanda'],
            'ET' => ['dial' => '+251', 'country' => 'Ethiopia'],
            'EG' => ['dial' => '+20', 'country' => 'Egypt'],
            'MA' => ['dial' => '+212', 'country' => 'Morocco'],
            'FR' => ['dial' => '+33', 'country' => 'France'],
            'DE' => ['dial' => '+49', 'country' => 'Germany'],
            'IT' => ['dial' => '+39', 'country' => 'Italy'],
            'ES' => ['dial' => '+34', 'country' => 'Spain'],
            'PT' => ['dial' => '+351', 'country' => 'Portugal'],
            'NL' => ['dial' => '+31', 'country' => 'Netherlands'],
            'BE' => ['dial' => '+32', 'country' => 'Belgium'],
            'CH' => ['dial' => '+41', 'country' => 'Switzerland'],
            'IE' => ['dial' => '+353', 'country' => 'Ireland'],
            'AT' => ['dial' => '+43', 'country' => 'Austria'],
            'SE' => ['dial' => '+46', 'country' => 'Sweden'],
            'NO' => ['dial' => '+47', 'country' => 'Norway'],
            'DK' => ['dial' => '+45', 'country' => 'Denmark'],
            'FI' => ['dial' => '+358', 'country' => 'Finland'],
            'PL' => ['dial' => '+48', 'country' => 'Poland'],
            'TR' => ['dial' => '+90', 'country' => 'Turkey'],
            'SA' => ['dial' => '+966', 'country' => 'Saudi Arabia'],
            'QA' => ['dial' => '+974', 'country' => 'Qatar'],
            'KW' => ['dial' => '+965', 'country' => 'Kuwait'],
            'BH' => ['dial' => '+973', 'country' => 'Bahrain'],
            'OM' => ['dial' => '+968', 'country' => 'Oman'],
            'IN' => ['dial' => '+91', 'country' => 'India'],
            'CN' => ['dial' => '+86', 'country' => 'China'],
            'JP' => ['dial' => '+81', 'country' => 'Japan'],
            'KR' => ['dial' => '+82', 'country' => 'South Korea'],
            'SG' => ['dial' => '+65', 'country' => 'Singapore'],
            'MY' => ['dial' => '+60', 'country' => 'Malaysia'],
            'ID' => ['dial' => '+62', 'country' => 'Indonesia'],
            'AU' => ['dial' => '+61', 'country' => 'Australia'],
            'NZ' => ['dial' => '+64', 'country' => 'New Zealand'],
            'BR' => ['dial' => '+55', 'country' => 'Brazil'],
            'MX' => ['dial' => '+52', 'country' => 'Mexico'],
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
