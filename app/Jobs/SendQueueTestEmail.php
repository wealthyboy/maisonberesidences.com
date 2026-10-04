<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendQueueTestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $recipient = 'jacob.atam@gmail.com';
        $processedAt = now();
        $queueConnection = (string) ($this->connection ?: config('queue.default'));
        $appUrl = (string) config('app.url');

        Mail::raw(
            implode("\n", [
                'Maison Be queue test completed successfully.',
                '',
                'This email was sent from a Laravel queued job.',
                'If you received it, the queue worker and email transport are processing jobs correctly.',
                '',
                'Processed at: '.$processedAt->format('Y-m-d H:i:s T'),
                'Queue connection: '.$queueConnection,
                'Application: '.$appUrl,
            ]),
            function ($message) use ($recipient): void {
                $message
                    ->to($recipient)
                    ->subject('Maison Be queue test successful');
            }
        );

        Log::info('Maison Be queue test email processed successfully.', [
            'recipient' => $recipient,
            'queue_connection' => $queueConnection,
            'processed_at' => $processedAt->toIso8601String(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Maison Be queue test email job failed.', [
            'recipient' => 'jacob.atam@gmail.com',
            'error' => $exception?->getMessage(),
        ]);
    }
}
