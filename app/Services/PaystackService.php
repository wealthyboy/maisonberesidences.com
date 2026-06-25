<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackService
{
    public function publicKey(): string
    {
        $key = (string) config('services.paystack.public_key');

        if ($key === '') {
            throw new RuntimeException('Online payments are not configured yet. Please contact Maison Be Reservations.');
        }

        return $key;
    }

    public function initialize(Invoice $invoice, string $callbackUrl): string
    {
        $this->ensureConfigured();

        $transaction = $this->initializeTransaction([
            'email' => $invoice->email,
            'amount' => (int) round((float) $invoice->total * 100),
            'currency' => $invoice->currency_code,
            'reference' => $invoice->payment_reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice,
                'coupon_code' => $invoice->coupon_code,
                'discount' => (float) $invoice->discount,
            ],
        ]);

        return (string) data_get($transaction, 'authorization_url');
    }

    public function initializeTransaction(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->client()->post('/transaction/initialize', $payload);

        if (! $response->successful() || ! data_get($response->json(), 'status')) {
            throw new RuntimeException((string) (data_get($response->json(), 'message') ?: 'Paystack could not initialise this payment.'));
        }

        return (array) data_get($response->json(), 'data', []);
    }

    public function verify(string $reference): array
    {
        $this->ensureConfigured();

        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful() || ! data_get($response->json(), 'status')) {
            throw new RuntimeException('Paystack could not verify this payment.');
        }

        return (array) data_get($response->json(), 'data', []);
    }

    public function webhookIsValid(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');

        return $secret !== ''
            && is_string($signature)
            && hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.paystack.base_url'))
            ->acceptJson()
            ->withToken((string) config('services.paystack.secret_key'))
            ->timeout(20);
    }

    private function ensureConfigured(): void
    {
        if ((string) config('services.paystack.secret_key') === '') {
            throw new RuntimeException('Online payments are not configured yet. Please contact Maison Be Reservations.');
        }
    }
}
