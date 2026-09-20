<?php

namespace App\Mail;

use App\Models\GuestCheckIn;
use App\Services\GuestDetailsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SelfCheckInSubmissionMail extends Mailable
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
        $disk = Storage::disk($this->checkIn->document_disk);

        return $this
            ->subject('Self check-in submitted: '.$this->details['reservation'])
            ->view('emails.self-check-in-submission')
            ->attachData($disk->get($this->checkIn->pdf_path), basename($this->checkIn->pdf_path), [
                'mime' => 'application/pdf',
            ])
            ->attachData($disk->get($this->checkIn->document_path), $this->checkIn->document_original_name, [
                'mime' => $this->checkIn->document_mime,
            ]);
    }
}
