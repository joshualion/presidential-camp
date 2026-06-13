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
            if (!Schema::hasColumn('polling_unit_results', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('result_sheet');
            }

            if (!Schema::hasColumn('polling_unit_results', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('polling_unit_results', 'review_status')) {
                $table->string('review_status', 30)->default('pending')->after('submitted_by');
            }

            if (!Schema::hasColumn('polling_unit_results', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_status');
            }

            if (!Schema::hasColumn('polling_unit_results', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('election_incidents', 'incident_type')) {
                $table->string('incident_type')->nullable()->after('polling_unit_id');
            }

            if (!Schema::hasColumn('election_incidents', 'severity')) {
                $table->string('severity', 30)->default('medium')->after('incident_type');
            }

            if (!Schema::hasColumn('election_incidents', 'status')) {
                $table->string('status', 30)->default('open')->after('severity');
            }

            if (!Schema::hasColumn('election_incidents', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('election_incidents', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            }
        });

        Schema::table('picture_evidences', function (Blueprint $table) {
            if (!Schema::hasColumn('picture_evidences', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('file_path');
            }

            if (!Schema::hasColumn('picture_evidences', 'verification_status')) {
                $table->string('verification_status', 30)->default('pending')->after('uploaded_by');
            }

            if (!Schema::hasColumn('picture_evidences', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_status');
            }

            if (!Schema::hasColumn('picture_evidences', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            }
        });

        Schema::table('video_evidences', function (Blueprint $table) {
            if (!Schema::hasColumn('video_evidences', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('file_path');
            }

            if (!Schema::hasColumn('video_evidences', 'verification_status')) {
                $table->string('verification_status', 30)->default('pending')->after('uploaded_by');
            }

            if (!Schema::hasColumn('video_evidences', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_status');
            }

            if (!Schema::hasColumn('video_evidences', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            }
        });

        $this->backfillLifecycleFields();
        $this->addIndexes();
        $this->addForeignKeys();
    }

    public function down(): void
    {
        $this->dropForeignIfExists('polling_unit_results', 'pur_submitted_by_fk');
        $this->dropForeignIfExists('polling_unit_results', 'pur_reviewed_by_fk');
        $this->dropForeignIfExists('election_incidents', 'ei_resolved_by_fk');
        $this->dropForeignIfExists('picture_evidences', 'pe_uploaded_by_fk');
        $this->dropForeignIfExists('picture_evidences', 'pe_verified_by_fk');
        $this->dropForeignIfExists('video_evidences', 've_uploaded_by_fk');
        $this->dropForeignIfExists('video_evidences', 've_verified_by_fk');

        $this->dropIndexIfExists('polling_unit_results', 'pur_review_status_idx');
        $this->dropIndexIfExists('polling_unit_results', 'pur_submitted_at_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_status_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_severity_idx');
        $this->dropIndexIfExists('election_incidents', 'ei_incident_type_idx');
        $this->dropIndexIfExists('picture_evidences', 'pe_verification_status_idx');
        $this->dropIndexIfExists('video_evidences', 've_verification_status_idx');

        Schema::table('polling_unit_results', function (Blueprint $table) {
            foreach (['submitted_at', 'submitted_by', 'review_status', 'reviewed_at', 'reviewed_by'] as $column) {
                if (Schema::hasColumn('polling_unit_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            foreach (['incident_type', 'severity', 'status', 'resolved_at', 'resolved_by'] as $column) {
                if (Schema::hasColumn('election_incidents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('picture_evidences', function (Blueprint $table) {
            foreach (['uploaded_by', 'verification_status', 'verified_at', 'verified_by'] as $column) {
                if (Schema::hasColumn('picture_evidences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('video_evidences', function (Blueprint $table) {
            foreach (['uploaded_by', 'verification_status', 'verified_at', 'verified_by'] as $column) {
                if (Schema::hasColumn('video_evidences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillLifecycleFields(): void
    {
        DB::table('polling_unit_results')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('COALESCE(created_at, updated_at)')]);

        DB::table('polling_unit_results')
            ->whereNull('review_status')
            ->update(['review_status' => 'pending']);

        DB::table('election_incidents')
            ->whereNull('status')
            ->update(['status' => 'open']);

        DB::table('election_incidents')
            ->whereNull('severity')
            ->update(['severity' => 'medium']);

        DB::table('picture_evidences')
            ->whereNull('verification_status')
            ->update(['verification_status' => 'pending']);

        DB::table('video_evidences')
            ->whereNull('verification_status')
            ->update(['verification_status' => 'pending']);
    }

    private function addIndexes(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'polling_unit_results', ['review_status'], 'pur_review_status_idx');
            $this->addIndexIfMissing($table, 'polling_unit_results', ['submitted_at'], 'pur_submitted_at_idx');
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'election_incidents', ['status'], 'ei_status_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['severity'], 'ei_severity_idx');
            $this->addIndexIfMissing($table, 'election_incidents', ['incident_type'], 'ei_incident_type_idx');
        });

        Schema::table('picture_evidences', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'picture_evidences', ['verification_status'], 'pe_verification_status_idx');
        });

        Schema::table('video_evidences', function (Blueprint $table) {
            $this->addIndexIfMissing($table, 'video_evidences', ['verification_status'], 've_verification_status_idx');
        });
    }

    private function addForeignKeys(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_submitted_by_fk', 'submitted_by');
            $this->addForeignIfMissing($table, 'polling_unit_results', 'pur_reviewed_by_fk', 'reviewed_by');
        });

        Schema::table('election_incidents', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'election_incidents', 'ei_resolved_by_fk', 'resolved_by');
        });

        Schema::table('picture_evidences', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'picture_evidences', 'pe_uploaded_by_fk', 'uploaded_by');
            $this->addForeignIfMissing($table, 'picture_evidences', 'pe_verified_by_fk', 'verified_by');
        });

        Schema::table('video_evidences', function (Blueprint $table) {
            $this->addForeignIfMissing($table, 'video_evidences', 've_uploaded_by_fk', 'uploaded_by');
            $this->addForeignIfMissing($table, 'video_evidences', 've_verified_by_fk', 'verified_by');
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
