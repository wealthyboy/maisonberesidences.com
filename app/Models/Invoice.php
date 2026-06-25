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
        'currency_code',
        'exchange_rate',
        'subtotal',
        'discount',
        'discount_type',
        'coupon_code',
        'caution_fee',
        'total',
        'payment_info',
        'payment_status',
        'payment_reference',
        'payment_payload',
        'paid_at',
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
            'exchange_rate' => 'decimal:6',
            'sent' => 'boolean',
            'payment_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
