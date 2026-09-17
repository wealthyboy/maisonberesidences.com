<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'adjustment_percent',
        'retrieved_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'adjustment_percent' => 'decimal:3',
            'retrieved_at' => 'datetime',
        ];
    }
}
