<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'caption',
        'image_id',
        'imageable_type',
        'imageable_id',
        'property_id',
    ];

    public function imageable()
    {
        return $this->morphTo();
    }
}
