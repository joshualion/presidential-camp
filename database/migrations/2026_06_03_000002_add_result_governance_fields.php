<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            if (!Schema::hasColumn('polling_unit_results', 'verification_status')) {
                $table->string('verification_status', 30)->default('submitted')->after('reviewed_by');
            }

            if (!Schema::hasColumn('polling_unit_results', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_status');
            }

            if (!Schema::hasColumn('polling_unit_results', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            }

            if (!Schema::hasColumn('polling_unit_results', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_by');
            }

            if (!Schema::hasColumn('polling_unit_results', 'dispute_status')) {
                $table->string('dispute_status', 30)->default('normal')->after('verification_notes');
            }

            if (!Schema::hasColumn('polling_unit_results', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable()->after('dispute_status');
            }

            if (!Schema::hasColumn('polling_unit_results', 'disputed_by')) {
                $table->unsignedBigInteger('disputed_by')->nullable()->after('disputed_at');
            }

            if (!Schema::hasColumn('polling_unit_results', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable()->after('disputed_by');
            }
        });

        DB::table('polling_unit_results')
            ->whereNull('verification_status')
            ->update(['verification_status' => 'submitted']);

        DB::table('polling_unit_results')
            ->whereNull('dispute_status')
            ->update(['dispute_status' => 'normal']);

        $this->addIndexes();
        $this->addForeignKeys();
        $this->ensureElectionReviewPermission();
    }

    public function down(): void
    {
        $this->dropForeignIfExists('polling_unit_results', 'pur_verified_by_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_disputed_by_fk');
        $this->dropIndexIfExists('polling_unit_results', 'pur_verification_status_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_dispute_status_idx');

        Schema::table('polling_unit_results', function (Blueprint $table) {
            foreach ([
                'verification_status',
                'verified_at',
                'verified_by',
                'verification_notes',
                'dispute_status',
                'disputed_at',
                'disputed_by',
                'dispute_reason',
            ] as $column) {
                if (Schema::hasColumn('polling_unit_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureElectionReviewPermission(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        if (!DB::table('permissions')->where('name', 'election-review')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'election-review',
                'guard_name' => 'web',
                'group_name' => 'superadmin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addIndexes(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'polling_unit_results', ['verification_status'], 'pur_verification_status_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['dispute_status'], 'pur_dispute_status_idx');
        });
    }

    private function addForeignKeys(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_verified_by_fk', 'verified_by');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_disputed_by_fk', 'disputed_by');
        });
    }

    private function addForeignIfMissing(Blueprint $table, string $tableName, string $foreignName, string $column): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!$this->foreignKeyExists($tableName, $foreignName)) {
            $table->foreign($column, $foreignName)->references('id')->on('users')->nullOnDelete();
        }
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName) && !$this->indexExistsOnColumns($tableName, $columns)) {
            $table->index($columns, $indexName);
        }
    }

    private function dropForeignIfExists(string $tableName, string $foreignName): void
    {
        if ($this->foreignKeyExists($tableName, $foreignName)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($foreignName));
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($indexName));
        }
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$tableName}')"))
                ->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        return !empty(DB::select('SHOW INDEX FROM `' . $tableName . '` WHERE Key_name = ?', [$indexName]));
    }

    private function indexExistsOnColumns(string $tableName, array $columns): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        $indexes = DB::select('SHOW INDEX FROM `' . $tableName . '`');
        $indexedColumns = [];

        foreach ($indexes as $index) {
            $indexedColumns[$index->Key_name][(int) $index->Seq_in_index] = $index->Column_name;
        }

        foreach ($indexedColumns as $indexColumns) {
            ksort($indexColumns);

            if (array_values($indexColumns) === array_values($columns)) {
                return true;
            }
        }

        return false;
    }

    private function foreignKeyExists(string $tableName, string $foreignName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return !empty(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ? AND CONSTRAINT_NAME = ?',
            [DB::getDatabaseName(), $tableName, 'FOREIGN KEY', $foreignName]
        ));
    }
};
