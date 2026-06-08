<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminModules
{
    public static function sections(): array
    {
        return config('admin.modules', []);
    }

    public static function all(): Collection
    {
        return collect(self::sections())
            ->flatMap(fn (array $section) => $section['items'] ?? [])
            ->values();
    }

    public static function find(string $slug): ?array
    {
        return self::all()->firstWhere('slug', $slug);
    }

    public static function allowedSlugs(): string
    {
        return self::all()
            ->pluck('slug')
            ->map(fn (string $slug) => preg_quote($slug, '/'))
            ->implode('|');
    }

    public static function routeName(string $slug): string
    {
        return 'admin.modules.' . Str::of($slug)->replace('-', '_');
    }
}
