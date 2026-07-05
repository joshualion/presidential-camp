<?php

namespace App\Support;

use InvalidArgumentException;

class SmsMoney
{
    public static function add(string $a, string $b): string { return self::format(self::units($a) + self::units($b)); }
    public static function subtract(string $a, string $b): string { return self::format(self::units($a) - self::units($b)); }
    public static function compare(string $a, string $b): int { return self::units($a) <=> self::units($b); }

    private static function units(string $amount): int
    {
        $amount = trim($amount);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d{1,6}))?$/', $amount, $matches)) throw new InvalidArgumentException('Invalid SMS wallet amount.');
        $units = ((int) $matches[2] * 1000000) + (int) str_pad($matches[3] ?? '', 6, '0');
        return ($matches[1] ?? '') === '-' ? -$units : $units;
    }

    private static function format(int $units): string
    {
        $sign = $units < 0 ? '-' : '';
        $units = abs($units);
        return $sign.intdiv($units, 1000000).'.'.str_pad((string) ($units % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
