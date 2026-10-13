<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutIdentityDocument extends Model
{
    protected $fillable = ['payment_reference', 'apartment_id', 'invoice_id', 'document_disk', 'document_path', 'document_mime', 'sent_at'];

    protected $hidden = ['document_disk', 'document_path'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
