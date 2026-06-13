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
            if (!Schema::hasColumn('polling_unit_results', 'state_id')) {
                $table->unsignedBigInteger('state_id')->nullable()->after('polling_unit_id');
            }

            if (!Schema::hasColumn('polling_unit_results', 'senatorial_district_id')) {
                $table->unsignedBigInteger('senatorial_district_id')->nullable()->after('state_id');
            }

            if (!Schema::hasColumn('polling_unit_results', 'federal_constituency_id')) {
                $table->unsignedBigInteger('federal_constituency_id')->nullable()->after('senatorial_district_id');
            }

            if (!Schema::hasColumn('polling_unit_results', 'lga_id')) {
                $table->unsignedBigInteger('lga_id')->nullable()->after('federal_constituency_id');
            }

            if (!Schema::hasColumn('polling_unit_results', 'ward_id')) {
                $table->unsignedBigInteger('ward_id')->nullable()->after('lga_id');
            }
        });

        Schema::table('votes', function (Blueprint $table) {
            if (!Schema::hasColumn('votes', 'senatorial_district_id')) {
                $table->unsignedBigInteger('senatorial_district_id')->nullable()->after('state_id');
            }

            if (!Schema::hasColumn('votes', 'federal_constituency_id')) {
                $table->unsignedBigInteger('federal_constituency_id')->nullable()->after('senatorial_district_id');
            }
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('election_incidents', 'senatorial_district_id')) {
                $table->unsignedBigInteger('senatorial_district_id')->nullable()->after('state_id');
            }

            if (!Schema::hasColumn('election_incidents', 'federal_constituency_id')) {
                $table->unsignedBigInteger('federal_constituency_id')->nullable()->after('senatorial_district_id');
            }
        });

        $this->backfillReportingKeys();
        $this->addForeignKeys();
        $this->addReportingIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_state_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_senatorial_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_federal_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_lga_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_ward_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_election_polling_unit_idx');

        $this->dropIndexIfExists('votes', 'votes_election_party_state_idx');
        $this->dropIndexIfExists('votes', 'votes_election_party_senatorial_idx');
        $this->dropIndexIfExists('votes', 'votes_election_party_federal_idx');
        $this->dropIndexIfExists('votes', 'votes_election_party_lga_idx');
        $this->dropIndexIfExists('votes', 'votes_election_party_ward_idx');

        $this->dropIndexIfExists('election_incidents', 'ei_election_state_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_election_senatorial_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_election_federal_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_election_lga_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_election_ward_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_election_polling_unit_idx');

        $this->dropForeignIfExists('polling_unit_results', 'pur_state_id_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_senatorial_district_id_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_federal_constituency_id_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_lga_id_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_ward_id_fk');
        $this->dropForeignIfExists('votes', 'votes_senatorial_district_id_fk');
        $this->dropForeignIfExists('votes', 'votes_federal_constituency_id_fk');
        $this->dropForeignIfExists('election_incidents', 'ei_senatorial_district_id_fk');
        $this->dropForeignIfExists('election_incidents', 'ei_federal_constituency_id_fk');

        Schema::table('polling_unit_results', function (Blueprint $table) {
            foreach (['state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id'] as $column) {
                if (Schema::hasColumn('polling_unit_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('votes', function (Blueprint $table) {
            foreach (['senatorial_district_id', 'federal_constituency_id'] as $column) {
                if (Schema::hasColumn('votes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            foreach (['senatorial_district_id', 'federal_constituency_id'] as $column) {
                if (Schema::hasColumn('election_incidents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillReportingKeys(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            UPDATE polling_unit_results pur
            INNER JOIN polling_units pu ON pu.id = pur.polling_unit_id
            LEFT JOIN wards w ON w.id = pu.ward_id
            LEFT JOIN local_government_areas lga ON lga.id = w.lga_id
            SET
                pur.state_id = lga.state_id,
                pur.senatorial_district_id = pu.senatorial_district_id,
                pur.federal_constituency_id = pu.federal_constituency_id,
                pur.lga_id = w.lga_id,
                pur.ward_id = pu.ward_id
            WHERE pur.polling_unit_id IS NOT NULL
        ");

        DB::statement("
            UPDATE votes v
            INNER JOIN polling_units pu ON pu.id = v.polling_unit_id
            SET
                v.senatorial_district_id = pu.senatorial_district_id,
                v.federal_constituency_id = pu.federal_constituency_id
            WHERE v.polling_unit_id IS NOT NULL
        ");

        DB::statement("
            UPDATE election_incidents ei
            INNER JOIN polling_units pu ON pu.id = ei.polling_unit_id
            SET
                ei.senatorial_district_id = pu.senatorial_district_id,
                ei.federal_constituency_id = pu.federal_constituency_id
            WHERE ei.polling_unit_id IS NOT NULL
        ");
    }

    private function addForeignKeys(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_state_id_fk', 'state_id', 'states');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_senatorial_district_id_fk', 'senatorial_district_id', 'senatorial_districts');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_federal_constituency_id_fk', 'federal_constituency_id', 'federal_constituencies');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_lga_id_fk', 'lga_id', 'local_government_areas');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_ward_id_fk', 'ward_id', 'wards');
        });

        Schema::table('votes', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'votes', 'votes_senatorial_district_id_fk', 'senatorial_district_id', 'senatorial_districts');
            $this->addForeignIfMissing($table, 'votes', 'votes_federal_constituency_id_fk', 'federal_constituency_id', 'federal_constituencies');
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'election_incidents', 'ei_senatorial_district_id_fk', 'senatorial_district_id', 'senatorial_districts');
            $this->addForeignIfMissing($table, 'election_incidents', 'ei_federal_constituency_id_fk', 'federal_constituency_id', 'federal_constituencies');
        });
    }

    private function addReportingIndexes(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'state_id'], 'pur_election_state_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'senatorial_district_id'], 'pur_election_senatorial_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'federal_constituency_id'], 'pur_election_federal_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'lga_id'], 'pur_election_lga_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'ward_id'], 'pur_election_ward_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['election_id', 'polling_unit_id'], 'pur_election_polling_unit_idx');
        });

        Schema::table('votes', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'votes', ['election_id', 'party_id', 'state_id'], 'votes_election_party_state_idx');
            $this->addIndexIfMissing($table, 'votes', ['election_id', 'party_id', 'senatorial_district_id'], 'votes_election_party_senatorial_idx');
            $this->addIndexIfMissing($table, 'votes', ['election_id', 'party_id', 'federal_constituency_id'], 'votes_election_party_federal_idx');
            $this->addIndexIfMissing($table, 'votes', ['election_id', 'party_id', 'lga_id'], 'votes_election_party_lga_idx');
            $this->addIndexIfMissing($table, 'votes', ['election_id', 'party_id', 'ward_id'], 'votes_election_party_ward_idx');
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'state_id'], 'ei_election_state_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'senatorial_district_id'], 'ei_election_senatorial_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'federal_constituency_id'], 'ei_election_federal_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'lga_id'], 'ei_election_lga_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'ward_id'], 'ei_election_ward_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['election_id', 'polling_unit_id'], 'ei_election_polling_unit_idx');
        });
    }

    private function addForeignIfMissing(Blueprint $table, string $tableName, string $foreignName, string $column, string $referenceTable): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!$this->foreignKeyExists($tableName, $foreignName)) {
            $table->foreign($column, $foreignName)->references('id')->on($referenceTable)->nullOnDelete();
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
