<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCloudbedsAvailabilityWebhook;
use App\Services\CloudbedsRoomBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudbedsAvailabilityWebhookController extends Controller
{
    public function __construct(private readonly CloudbedsRoomBlockService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->service->webhookIsValid($request->header($this->service->webhookHeaderName()))) {
            Log::warning('Cloudbeds availability webhook rejected because its shared secret was invalid.', [
                'ip' => $request->ip(),
                'event' => $request->json('event'),
            ]);

            return response()->json(['message' => 'Invalid webhook credential.'], 401);
        }

        $payload = $request->json()->all();
        $event = strtolower(trim((string) ($payload['event'] ?? '')));

        if (! str_starts_with($event, 'roomblock/') && $event !== 'availability/closeout_changed') {
            return response()->json(['status' => 'ignored']);
        }

        ProcessCloudbedsAvailabilityWebhook::dispatch($payload);

        // Cloudbeds retries non-2XX deliveries. Queue the work and acknowledge
        // immediately so duplicate webhook deliveries stay minimal.
        return response()->json(['status' => 'accepted'], 202);
    }
}
