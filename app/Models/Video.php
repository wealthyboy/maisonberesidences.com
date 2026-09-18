<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'disk',
        'path',
        'encoded',
        'encoded_path',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'encoded' => 'boolean',
        ];
    }

    public function videoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getSourceUrlAttribute(): ?string
    {
        return filled($this->path) ? Storage::disk($this->disk ?: 'spaces')->url($this->path) : null;
    }

    public function getPlaybackUrlAttribute(): ?string
    {
        $path = $this->encoded && filled($this->encoded_path) ? $this->encoded_path : $this->path;

        return filled($path) ? Storage::disk($this->disk ?: 'spaces')->url($path) : null;
    }
}
