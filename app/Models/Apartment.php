<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'property_id',
        'price',
        'sale_price',
        'slug',
        'image',
        'quantity',
        'max_adults',
        'no_of_rooms',
        'toilets',
        'type',
        'uuid',
        'price_mode',
        'apartment_id',
        'video_link',
        'image_link',
        'allow',
        'floor',
        'teaser',
        'owner_email',
        'wifi_password',
        'wifi_ssid',
        'bedroom_1',
        'bedroom_2',
        'bedroom_3',
        'bedroom_4',
        'bedroom_5',
        'bedroom_6',
        'description',
        'sale_price_expires',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'toilets' => 'decimal:1',
            'allow' => 'boolean',
            'sale_price_expires' => 'date',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('image_id')->orderBy('id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'apartment_attribute')->withTimestamps();
    }
}
