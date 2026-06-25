<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    protected $table = 'information';

    protected $fillable = [
        'title',
        'name',
        'slug',
        'description',
        'custom_link',
        'sort_order',
        'teaser',
        'blog',
    ];
}
