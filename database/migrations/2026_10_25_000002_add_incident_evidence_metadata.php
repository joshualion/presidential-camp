<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('system_settings', 'incident_evidence_rules')) {
            Schema::table('system_settings', fn (Blueprint $table) => $table->text('incident_evidence_rules')->nullable());
        }
        if (! Schema::hasColumn('election_incidents', 'polling_unit_result_id')) {
            Schema::table('election_incidents', fn (Blueprint $table) => $table->unsignedBigInteger('polling_unit_result_id')->nullable()->index());
        }
        Schema::table('election_incidents', fn (Blueprint $table) => $table->text('remarks')->nullable()->change());
        foreach (['picture_evidences', 'video_evidences'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'legacy_public_path')) { $table->string('legacy_public_path')->nullable(); }
                if (! Schema::hasColumn($name, 'disk')) { $table->string('disk', 40)->nullable(); }
                if (! Schema::hasColumn($name, 'mime_type')) { $table->string('mime_type', 100)->nullable(); }
                if (! Schema::hasColumn($name, 'original_name')) { $table->string('original_name')->nullable(); }
                if (! Schema::hasColumn($name, 'size_bytes')) { $table->unsignedBigInteger('size_bytes')->nullable(); }
            });
        }
    }

    public function down(): void
    {
        // Retain evidence metadata and historical associations; no destructive rollback.
    }
};
