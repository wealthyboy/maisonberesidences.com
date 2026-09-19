<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_usd',
        'is_active',
        'available_for_all_apartments',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'is_active' => 'boolean',
            'available_for_all_apartments' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'apartment_additional_service')->withTimestamps();
    }
}
