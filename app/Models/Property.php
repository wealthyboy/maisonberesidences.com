<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'mode',
        'status',
        'address',
        'city',
        'state',
        'country',
        'location_full_name',
        'price',
        'price_mode',
        'sale_price',
        'size',
        'bedrooms',
        'toilets',
        'bathrooms',
        'max_guests',
        'check_in_time',
        'check_out_time',
        'image',
        'description',
        'allow',
        'featured',
        'is_refundable',
        'cancellation_message',
        'cancellation_fee',
        'virtual_tour',
        'is_price_negotiable',
        'is_shortlet',
        'is_featured',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
            'toilets' => 'decimal:1',
            'allow' => 'boolean',
            'featured' => 'boolean',
            'is_refundable' => 'boolean',
            'is_price_negotiable' => 'boolean',
            'is_shortlet' => 'boolean',
            'is_featured' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }
}
