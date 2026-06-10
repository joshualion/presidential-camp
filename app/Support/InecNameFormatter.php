<?php

namespace App\Support;

use Illuminate\Support\Str;

class InecNameFormatter
{
    public static function display(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = preg_replace('/\s*\/\s*/', '/', $value) ?? '';

        if ($value === '') {
            return '';
        }

        return collect(explode('/', $value))
            ->map(fn ($part) => Str::title(Str::lower(trim($part))))
            ->implode('/');
    }

    public static function key(?string $value): string
    {
        return Str::lower(self::display($value));
    }

    public static function slug(?string $value): string
    {
        return Str::slug(self::display($value));
    }
}
