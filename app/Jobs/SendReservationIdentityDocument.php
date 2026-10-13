<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\CheckoutIdentityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReservationIdentityDocument implements ShouldQueue
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
        return [60, 300, 900, 1800];
    }

    public function handle(CheckoutIdentityService $identities): void
    {
        $invoice = Invoice::find($this->invoiceId);
        if ($invoice && $invoice->payment_status === 'paid') {
            $identities->sendForInvoice($invoice);
        }
    }
}
