<?php

namespace App\Http\Controllers;

use App\Services\PaystackBookingService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly PaystackBookingService $bookings,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        Log::info($request->all());

        if (! $this->paystack->webhookIsValid($payload, $request->header('x-paystack-signature'))) {
            Log::warning('Paystack webhook rejected because signature is invalid.', [
                'ip' => $request->ip(),
                'signature' => $request->header('x-paystack-signature'),
                'raw_payload' => $payload,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = $request->json()->all();

        if (data_get($event, 'event') !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = (string) data_get($event, 'data.reference');

        try {
            $this->bookings->processReference($reference, $event);
        } catch (\Throwable $exception) {
            Log::error('Paystack webhook processing failed.', ['reference' => $reference, 'message' => $exception->getMessage()]);

            return response()->json(['message' => 'Could not process payment.'], 422);
        }

        return response()->json(['status' => 'ok']);
    }
}
