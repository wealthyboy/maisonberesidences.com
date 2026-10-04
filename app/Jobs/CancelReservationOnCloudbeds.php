<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\CloudbedsApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CancelReservationOnCloudbeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(public int $invoiceId)
    {
        $this->onConnection('database');
    }

    public function backoff(): array
    {
        return [15, 60, 180, 600, 1800];
    }

    public function handle(CloudbedsApiService $cloudbeds): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);

        if (! $invoice) {
            Log::warning('Cloudbeds cancellation skipped because invoice no longer exists.', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        if ($invoice->reservation_status !== 'canceled') {
            Log::info('Cloudbeds cancellation skipped because reservation is not canceled locally.', [
                'invoice_id' => $invoice->id,
                'invoice' => $invoice->invoice,
                'reservation_status' => $invoice->reservation_status,
            ]);

            return;
        }

        Cache::lock('cloudbeds-paid-reservation-'.$invoice->id, 120)->block(10, function () use ($cloudbeds, $invoice): void {
            $invoice->refresh();

            if ($invoice->reservation_status !== 'canceled') {
                return;
            }

            $payload = $invoice->payment_payload ?? [];

            if ((string) data_get($payload, 'cloudbeds.status') === 'canceled') {
                Log::info('Cloudbeds cancellation skipped because reservation is already canceled.', [
                    'invoice_id' => $invoice->id,
                    'invoice' => $invoice->invoice,
                    'reservation_id' => data_get($payload, 'cloudbeds.reservation.reservation_id'),
                ]);

                return;
            }

            $reservationId = trim((string) data_get($payload, 'cloudbeds.reservation.reservation_id'));

            if ($reservationId === '') {
                $cloudbeds->recordLocalCancellation($invoice);

                return;
            }

            try {
                $cloudbeds->cancelReservation($invoice);
            } catch (Throwable $exception) {
                $cloudbeds->recordCancellationFailure($invoice->fresh(), $exception);

                throw $exception;
            }
        });
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cloudbeds reservation cancellation job exhausted all retries.', [
            'invoice_id' => $this->invoiceId,
            'message' => $exception?->getMessage(),
        ]);
    }
}
