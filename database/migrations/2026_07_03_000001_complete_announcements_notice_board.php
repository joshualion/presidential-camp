<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('scope_type', 40)->default('public')->after('audience')->index();
            $table->foreignId('region_id')->nullable()->after('scope_type')->constrained('regions')->nullOnDelete();
            $table->foreignId('senatorial_district_id')->nullable()->after('state_id')->constrained('senatorial_districts')->nullOnDelete();
            $table->foreignId('federal_constituency_id')->nullable()->after('senatorial_district_id')->constrained('federal_constituencies')->nullOnDelete();
            $table->unsignedTinyInteger('priority')->default(1)->after('is_active')->index();
            $table->timestamp('published_at')->nullable()->after('priority')->index();
            $table->timestamp('expires_at')->nullable()->after('published_at')->index();
        });

        DB::table('announcements')->orderBy('id')->each(function ($announcement) {
            $scopeType = match ($announcement->audience) {
                'state' => 'state',
                'lga' => 'lga',
                'ward' => 'ward',
                'pu' => 'polling_unit',
                default => 'public',
            };

            DB::table('announcements')->where('id', $announcement->id)->update([
                'scope_type' => $scopeType,
                'published_at' => $announcement->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('senatorial_district_id');
            $table->dropConstrainedForeignId('federal_constituency_id');
            $table->dropColumn(['scope_type', 'priority', 'published_at', 'expires_at']);
        });
    }
};
