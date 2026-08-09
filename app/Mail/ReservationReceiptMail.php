<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build(): self
    {
        return $this
            ->subject('Your Maison Be Residences booking is confirmed')
            ->view('emails.reservation-receipt');
    }
}
