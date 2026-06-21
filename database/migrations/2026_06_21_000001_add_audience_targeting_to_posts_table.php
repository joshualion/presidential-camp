<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'audience_type')) {
                $table->string('audience_type')->nullable()->after('audience')->index();
            }

            if (!Schema::hasColumn('posts', 'audience_scope_type')) {
                $table->string('audience_scope_type')->nullable()->after('audience_type')->index();
            }

            if (!Schema::hasColumn('posts', 'audience_scope_id')) {
                $table->unsignedBigInteger('audience_scope_id')->nullable()->after('audience_scope_type')->index();
            }

            if (!Schema::hasColumn('posts', 'audience_group_id')) {
                $table->unsignedBigInteger('audience_group_id')->nullable()->after('audience_scope_id')->index();
            }

            if (!Schema::hasColumn('posts', 'audience_metadata')) {
                $table->json('audience_metadata')->nullable()->after('audience_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            foreach (['audience_metadata', 'audience_group_id', 'audience_scope_id', 'audience_scope_type', 'audience_type'] as $column) {
                if (Schema::hasColumn('posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
