<?php

namespace App\Support;

class ThemeColor
{
    public static function normalize(?string $value, string $fallback = '#008751'): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $value)) { return $fallback; }
        $value = strtolower(ltrim($value, '#'));
        if (strlen($value) === 3) { $value = $value[0].$value[0].$value[1].$value[1].$value[2].$value[2]; }
        return '#'.$value;
    }

    public static function foreground(string $background): string
    {
        $luminance = self::luminance($background);
        return (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.05) ? '#ffffff' : '#000000';
    }

    public static function contrast(string $first, string $second): float
    {
        $a = self::luminance($first); $b = self::luminance($second);
        return (max($a, $b) + .05) / (min($a, $b) + .05);
    }

    private static function luminance(string $background): float
    {
        $linear = array_map(static function ($channel) {
            $value = hexdec($channel) / 255;
            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr(self::normalize($background), 1), 2));
        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }
}
