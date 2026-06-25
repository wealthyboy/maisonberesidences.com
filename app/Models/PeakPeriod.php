<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeakPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'increase_percent',
        'discount',
        'days_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'increase_percent' => 'decimal:2',
            'discount' => 'decimal:2',
            'days_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
