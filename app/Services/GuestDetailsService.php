<?php

namespace App\Services;

use App\Models\Invoice;

class GuestDetailsService
{
    public function fromInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('invoiceItems.apartment');
        $item = $invoice->invoiceItems->first();
        $firstName = trim((string) data_get($invoice->payment_payload, 'booking.first_name'));
        $lastName = trim((string) data_get($invoice->payment_payload, 'booking.last_name'));

        if ($firstName === '' && $lastName === '') {
            $parts = preg_split('/\s+/', trim((string) $invoice->full_name), 2) ?: [];
            $firstName = (string) ($parts[0] ?? '');
            $lastName = (string) ($parts[1] ?? '');
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) $invoice->email,
            'phone' => (string) $invoice->phone,
            'checkin' => $item?->checkin,
            'checkout' => $item?->checkout,
            'apartment' => (string) ($item?->name ?? ''),
            'reservation' => (string) $invoice->invoice,
        ];
    }
}
