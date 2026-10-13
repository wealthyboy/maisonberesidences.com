<?php

namespace App\Mail;

use App\Models\CheckoutIdentityDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationIdentityDocumentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public CheckoutIdentityDocument $document) {}

    public function build(): self
    {
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'][$this->document->document_mime];

        return $this->subject('Guest ID: '.$this->document->invoice->invoice)
            ->view('emails.reservation-identity-document')
            ->attachFromStorageDisk($this->document->document_disk, $this->document->document_path, 'guest-id.'.$extension, [
                'mime' => $this->document->document_mime,
            ]);
    }
}
