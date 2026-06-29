<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'amount',
        'from_value',
        'type',
        'status',
        'valid',
        'expires',
        'limits',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'from_value' => 'decimal:2',
            'status' => 'boolean',
            'valid' => 'boolean',
            'expires' => 'datetime',
            'limits' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->code;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires !== null && $this->expires->isPast();
    }

    public function getIsExhaustedAttribute(): bool
    {
        return $this->limits !== null && $this->used_count >= $this->limits;
    }

    public function getIsUsableAttribute(): bool
    {
        return $this->status && $this->valid && ! $this->is_expired && ! $this->is_exhausted;
    }
}
