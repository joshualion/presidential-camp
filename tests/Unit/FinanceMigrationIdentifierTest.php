<?php

namespace Tests\Unit;

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceMigrationIdentifierTest extends TestCase
{
    public function test_donation_migration_compiles_mysql_identifiers_within_the_database_limit(): void
    {
        $connection = new MySqlConnection(
            fn () => throw new \RuntimeException('This compilation test must not connect to a database.'),
            'synthetic_identifier_test',
            '',
            ['name' => 'identifier-test', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'version' => '8.0.41']
        );
        $original = Schema::getFacadeRoot();
        try {
            Schema::swap($connection->getSchemaBuilder());
            $migration = require __DIR__.'/../../database/migrations/2026_11_07_000026_add_reviewed_paystack_donations.php';
            $queries = $connection->pretend(fn () => $migration->up());
        } finally {
            Schema::swap($original);
        }

        $identifiers = [];
        foreach ($queries as $query) {
            preg_match_all('/\b(?:index|key|constraint)\s+`([^`]+)`/i', $query['query'], $names);
            array_push($identifiers, ...$names[1]);
        }
        $this->assertNotEmpty($identifiers);
        foreach ($identifiers as $name) {
            $this->assertLessThanOrEqual(64, strlen($name), $name);
        }
    }
}
