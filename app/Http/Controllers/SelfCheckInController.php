<?php

namespace App\Http\Controllers;

use App\Mail\SelfCheckInSubmissionMail;
use App\Mail\SelfCheckInWelcomeMail;
use App\Models\GuestCheckIn;
use App\Models\Invoice;
use App\Services\GuestDetailsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SelfCheckInController extends Controller
{
    public function __construct(private readonly GuestDetailsService $guestDetails) {}

    public function show(Invoice $invoice): View
    {
        $invoice->load(['invoiceItems.apartment', 'guestCheckIn']);

        return view('reservations.self-check-in', [
            'invoice' => $invoice,
            'details' => $this->guestDetails->fromInvoice($invoice),
            'checkIn' => $invoice->guestCheckIn,
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->guestCheckIn()->exists()) {
            return back()->with('checkin_success', 'Your self check-in has already been submitted.');
        }

        $validated = $request->validate([
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        $document = $validated['identity_document'];
        $disk = 'local';
        $directory = 'self-check-ins/'.$invoice->id;
        $documentName = Str::uuid().'.'.strtolower($document->getClientOriginalExtension());
        $documentPath = $document->storeAs($directory, $documentName, $disk);
        $details = $this->guestDetails->fromInvoice($invoice);
        $mime = (string) $document->getMimeType();
        $pdfPath = $directory.'/maison-be-self-check-in-'.$invoice->invoice.'.pdf';

        try {
            $documentOriginalName = $document->getClientOriginalName();
            $pdf = Pdf::loadView('pdf.self-check-in', compact('invoice', 'details', 'documentOriginalName'))
                ->setPaper('a4');
            Storage::disk($disk)->put($pdfPath, $pdf->output());

            $checkIn = GuestCheckIn::create([
                'invoice_id' => $invoice->id,
                'document_disk' => $disk,
                'document_path' => $documentPath,
                'document_original_name' => $documentOriginalName,
                'document_mime' => $mime,
                'pdf_path' => $pdfPath,
                'submitted_at' => now(),
            ]);

            $checkIn->setRelation('invoice', $invoice->loadMissing('invoiceItems.apartment'));

            $this->sendEmails($checkIn);
        } catch (\Throwable $exception) {
            GuestCheckIn::query()->where('invoice_id', $invoice->id)->delete();
            Storage::disk($disk)->delete([$documentPath, $pdfPath]);
            report($exception);

            return back()->withErrors(['identity_document' => 'We could not complete your check-in. Please try again.']);
        }

        return back()->with('checkin_success', 'Your self check-in is complete. A welcome confirmation has been emailed to you.');
    }

    public function resend(Invoice $invoice): RedirectResponse
    {
        $checkIn = $invoice->guestCheckIn()->firstOrFail();
        $checkIn->setRelation('invoice', $invoice->loadMissing('invoiceItems.apartment'));

        try {
            $this->sendEmails($checkIn);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('mail_error', 'The email could not be sent. Please contact reservations for assistance.');
        }

        return back()->with('mail_success', 'The self check-in emails have been sent again.');
    }

    private function sendEmails(GuestCheckIn $checkIn): void
    {
        $invoice = $checkIn->invoice;

        Log::info('Sending self check-in emails.', [
            'invoice_id' => $invoice->id,
            'check_in_id' => $checkIn->id,
            'guest_email' => $invoice->email,
        ]);

        Mail::to('reservations@maisonberesidences.com')
            ->bcc('md@maisonberesidences.com')
            ->send(new SelfCheckInSubmissionMail($checkIn));

        Mail::to($invoice->email)
            ->bcc(['reservations@maisonberesidences.com', 'md@maisonberesidences.com'])
            ->send(new SelfCheckInWelcomeMail($checkIn));

        Log::info('Self check-in emails accepted by mail transport.', [
            'invoice_id' => $invoice->id,
            'check_in_id' => $checkIn->id,
        ]);
    }
}
