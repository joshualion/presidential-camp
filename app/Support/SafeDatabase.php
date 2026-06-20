<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SafeDatabase
{
    public static function canConnect(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function hasTable(string $table): bool
    {
        try {
            return self::canConnect() && Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
