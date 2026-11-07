<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_proposal_controls')) {
            Schema::create('campaign_finance_proposal_controls', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('proposal_id')->primary();
                $table->unsignedInteger('revision')->default(1);
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('assigned_at')->nullable();
            });
        }
        if (!Schema::hasTable('campaign_finance_proposal_events')) {
            Schema::create('campaign_finance_proposal_events', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('proposal_id')->index();
                $table->unsignedBigInteger('actor_id');
                $table->unsignedInteger('revision');
                $table->string('action', 24);
                $table->string('from_status', 24)->nullable();
                $table->string('to_status', 24);
                $table->json('details');
                $table->timestamp('created_at');
                $table->unique(['proposal_id', 'revision']);
            });
        }
        $locationColumns = ['region_id', 'state_id', 'senatorial_district_id',
            'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'];
        if (!Schema::hasTable('campaign_finance_proposal_locations')) {
            Schema::create('campaign_finance_proposal_locations', function (Blueprint $table) use ($locationColumns) {
                $table->engine = 'InnoDB';
                $table->unsignedBigInteger('proposal_id')->primary();
                foreach ($locationColumns as $column) {
                    $table->unsignedBigInteger($column)->nullable();
                }
            });
        }
        // MySQL can leave the table and earlier indexes behind after a failed DDL statement.
        foreach ($locationColumns as $column) {
            if (!Schema::hasIndex('campaign_finance_proposal_locations', [$column])) {
                Schema::table('campaign_finance_proposal_locations', function (Blueprint $table) use ($column) {
                    $table->index($column, 'finance_proposal_loc_'.$column.'_idx');
                });
            }
        }
        // Existing intake records (from pre-1.2.6 checkpoints) remain accessible.
        // Their initial location is reconstructed once; all future records snapshot at creation.
        DB::table('campaign_finance_funding_proposals as proposals')
            ->join('users', 'users.id', '=', 'proposals.requested_by')
            ->select('proposals.id', 'proposals.requested_by', 'proposals.status',
                'users.region_id', 'users.state_id', 'users.senatorial_district_id',
                'users.federal_constituency_id', 'users.lga_id', 'users.ward_id', 'users.polling_unit_id')
            ->orderBy('proposals.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('campaign_finance_proposal_locations')->insertOrIgnore([
                        'proposal_id' => $row->id,
                        'region_id' => $row->region_id,
                        'state_id' => $row->state_id,
                        'senatorial_district_id' => $row->senatorial_district_id,
                        'federal_constituency_id' => $row->federal_constituency_id,
                        'lga_id' => $row->lga_id,
                        'ward_id' => $row->ward_id,
                        'polling_unit_id' => $row->polling_unit_id,
                    ]);
                    DB::table('campaign_finance_proposal_controls')->insertOrIgnore([
                        'proposal_id' => $row->id, 'revision' => 1,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Retain finance approvals and their original jurisdiction for audit.
    }
};
