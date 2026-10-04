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
use RuntimeException;
use Throwable;

class SyncReservationToCloudbeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    public function __construct(public int $invoiceId)
    {
        // Keep this on the same database queue that was verified with the
        // backend queue-email test.
        $this->onConnection('database');
    }

    public function backoff(): array
    {
        return [15, 60, 180, 600, 1800];
    }

    public function handle(CloudbedsApiService $cloudbeds): void
    {
        $invoice = Invoice::query()
            ->with('invoiceItems.apartment')
            ->find($this->invoiceId);

        if (! $invoice) {
            Log::warning('Cloudbeds queue sync skipped because invoice no longer exists.', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        if ($invoice->payment_status !== 'paid') {
            Log::warning('Cloudbeds queue sync skipped because invoice is not paid.', [
                'invoice_id' => $invoice->id,
                'invoice' => $invoice->invoice,
                'payment_status' => $invoice->payment_status,
            ]);

            return;
        }

        Cache::lock('cloudbeds-paid-reservation-'.$invoice->id, 120)->block(10, function () use ($cloudbeds, $invoice): void {
            $invoice->refresh()->loadMissing('invoiceItems.apartment');
            $payload = $invoice->payment_payload ?? [];

            if (filled(data_get($payload, 'cloudbeds.reservation.reservation_id'))) {
                Log::info('Cloudbeds queue sync skipped because reservation is already synced.', [
                    'invoice_id' => $invoice->id,
                    'invoice' => $invoice->invoice,
                    'reservation_id' => data_get($payload, 'cloudbeds.reservation.reservation_id'),
                ]);

                return;
            }

            $booking = (array) data_get($payload, 'booking', []);

            if ($booking === []) {
                throw new RuntimeException('Cloudbeds queue sync could not find the stored booking payload.');
            }

            $this->markSyncing($invoice);

            try {
                $roomType = $this->preferredRoomType($cloudbeds, $booking);
                $cloudbeds->createReservation($invoice, $booking, $roomType);
            } catch (Throwable $exception) {
                $cloudbeds->recordSyncFailure($invoice->fresh(), $exception);

                throw $exception;
            }
        });
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cloudbeds reservation sync job exhausted all retries.', [
            'invoice_id' => $this->invoiceId,
            'message' => $exception?->getMessage(),
        ]);
    }

    private function preferredRoomType(CloudbedsApiService $cloudbeds, array $booking): ?array
    {
        $roomTypeId = trim((string) data_get($booking, 'cloudbeds_room_type_id'));

        if ($roomTypeId === '') {
            return null;
        }

        $roomType = collect($cloudbeds->roomTypes())->first(function (array $candidate) use ($roomTypeId): bool {
            return in_array($roomTypeId, array_map('strval', (array) ($candidate['room_type_ids'] ?? [])), true);
        });

        if (! is_array($roomType)) {
            return null;
        }

        $roomType['id'] = $roomTypeId;

        return $roomType;
    }

    private function markSyncing(Invoice $invoice): void
    {
        $payload = $invoice->payment_payload ?? [];
        $cloudbeds = (array) data_get($payload, 'cloudbeds', []);

        $cloudbeds['status'] = 'syncing';
        $cloudbeds['started_at'] = now()->toIso8601String();
        $cloudbeds['attempt'] = $this->attempts();

        $payload['cloudbeds'] = $cloudbeds;
        $invoice->forceFill(['payment_payload' => $payload])->save();
    }
}
