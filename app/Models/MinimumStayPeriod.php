<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinimumStayPeriod extends Model
{
    protected $fillable = ['title', 'starts_on', 'ends_on', 'minimum_nights', 'applies_to_all', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'ends_on' => 'date', 'minimum_nights' => 'integer',
            'applies_to_all' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    public function apartments()
    {
        return $this->belongsToMany(Apartment::class, 'apartment_minimum_stay_period');
    }
}
