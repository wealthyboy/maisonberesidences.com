<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestCheckIn extends Model
{
    protected $fillable = [
        'invoice_id',
        'document_disk',
        'document_path',
        'document_original_name',
        'document_mime',
        'pdf_path',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
