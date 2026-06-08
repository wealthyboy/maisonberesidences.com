<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice',
        'full_name',
        'email',
        'phone',
        'address',
        'country',
        'currency',
        'subtotal',
        'discount',
        'discount_type',
        'caution_fee',
        'total',
        'payment_info',
        'description',
        'sent',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'caution_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'sent' => 'boolean',
        ];
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
