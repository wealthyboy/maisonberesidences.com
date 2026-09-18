<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AdminModuleRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_slug',
        'title',
        'status',
        'summary',
        'content',
        'meta',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function video(): MorphOne
    {
        return $this->morphOne(Video::class, 'videoable');
    }
}
