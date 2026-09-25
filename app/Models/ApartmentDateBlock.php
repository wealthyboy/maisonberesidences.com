<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApartmentDateBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'starts_on',
        'ends_on',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function apartments()
    {
        return $this->belongsToMany(Apartment::class, 'apartment_date_block');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOverlapping(Builder $query, CarbonInterface $checkin, CarbonInterface $checkout): Builder
    {
        return $query
            ->where('starts_on', '<', $checkout->toDateString())
            ->where('ends_on', '>', $checkin->toDateString());
    }
}
