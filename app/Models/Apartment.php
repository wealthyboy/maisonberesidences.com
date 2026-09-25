<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'property_id',
        'price',
        'sale_price',
        'slug',
        'image',
        'quantity',
        'max_adults',
        'no_of_rooms',
        'size_sq_ft',
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
            'sort_order' => 'integer',
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

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('allow', true);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('image_id')->orderBy('id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function dateBlocks()
    {
        return $this->belongsToMany(ApartmentDateBlock::class, 'apartment_date_block');
    }

    public function scopeAvailableFor(Builder $query, CarbonInterface $checkin, CarbonInterface $checkout): Builder
    {
        return $query
            ->whereDoesntHave('invoiceItems', function (Builder $invoiceItems) use ($checkin, $checkout): void {
                $invoiceItems
                    ->whereHas('invoice', fn (Builder $invoice) => $invoice->where('payment_status', 'paid'))
                    ->whereNotNull('checkin')
                    ->whereNotNull('checkout')
                    ->where('checkin', '<', $checkout)
                    ->where('checkout', '>', $checkin);
            })
            ->whereDoesntHave('dateBlocks', fn (Builder $blocks) => $blocks->overlapping($checkin, $checkout));
    }

    public function isBlockedFor(CarbonInterface $checkin, CarbonInterface $checkout): bool
    {
        return $this->dateBlocks()->overlapping($checkin, $checkout)->exists();
    }

    public function isAvailableFor(CarbonInterface $checkin, CarbonInterface $checkout): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->availableFor($checkin, $checkout)
            ->exists();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'apartment_attribute')->withTimestamps();
    }

    public function additionalServices()
    {
        return $this->belongsToMany(AdditionalService::class, 'apartment_additional_service')->withTimestamps();
    }
}
