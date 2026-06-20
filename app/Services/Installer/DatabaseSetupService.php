<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class DatabaseSetupService
{
    public function test(array $values): array
    {
        $name = 'installer_test';

        Config::set("database.connections.$name", [
            'driver' => $values['DB_CONNECTION'] ?? 'mysql',
            'host' => $values['DB_HOST'] ?? '127.0.0.1',
            'port' => $values['DB_PORT'] ?? '3306',
            'database' => $values['DB_DATABASE'] ?? '',
            'username' => $values['DB_USERNAME'] ?? '',
            'password' => $values['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);

        try {
            DB::connection($name)->getPdo();
            DB::purge($name);

            return ['ok' => true, 'message' => 'Database connection successful.'];
        } catch (Throwable $e) {
            DB::purge($name);

            return ['ok' => false, 'message' => $this->cleanMessage($e->getMessage())];
        }
    }

    private function cleanMessage(string $message): string
    {
        return preg_replace('/password=[^;\s]+/i', 'password=******', $message) ?: 'Database connection failed.';
    }
}
