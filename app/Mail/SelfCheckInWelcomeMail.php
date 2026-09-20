<?php

namespace App\Mail;

use App\Models\GuestCheckIn;
use App\Services\GuestDetailsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SelfCheckInWelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $details;

    public function __construct(public GuestCheckIn $checkIn)
    {
        $this->details = app(GuestDetailsService::class)->fromInvoice($checkIn->invoice);
    }

    public function build(): self
    {
        return $this
            ->subject('Welcome to Maison Be Residences')
            ->view('emails.self-check-in-welcome');
    }
}
