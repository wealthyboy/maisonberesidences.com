<?php

namespace App\Jobs;

use App\Services\CloudbedsRoomBlockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCloudbedsRoomBlocks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public int $days = 500)
    {
        $this->onConnection('database');
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(CloudbedsRoomBlockService $service): void
    {
        $result = $service->syncExistingBlocks($this->days);

        Log::info('Cloudbeds room blocks reconciled into Maison Be.', $result);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cloudbeds room-block reconciliation failed.', [
            'days' => $this->days,
            'message' => $exception?->getMessage(),
        ]);
    }
}
