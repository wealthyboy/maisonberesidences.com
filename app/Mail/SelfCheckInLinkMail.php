<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\GuestDetailsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SelfCheckInLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $details;

    public string $selfCheckInUrl;

    public function __construct(public Invoice $invoice)
    {
        $this->details = app(GuestDetailsService::class)->fromInvoice($invoice);
        $this->selfCheckInUrl = URL::signedRoute('reservations.self-check-in', $invoice);
    }

    public function build(): self
    {
        return $this
            ->subject('Your Maison Be Residences self check-in link')
            ->view('emails.self-check-in-link');
    }
}
