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

class ProcessCloudbedsAvailabilityWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;

    public function __construct(public array $payload)
    {
        $this->onConnection('database');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 180, 600];
    }

    public function handle(CloudbedsRoomBlockService $service): void
    {
        $service->handleWebhook($this->payload);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cloudbeds availability webhook job exhausted all retries.', [
            'event' => $this->payload['event'] ?? null,
            'room_block_id' => $this->payload['roomBlockID'] ?? null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
