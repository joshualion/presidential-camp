<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COMMERCIAL_VALUES = "'presidential', 'governorship', 'senatorial', 'federal_constituency', 'chairmanship'";
    private const COMPATIBLE_VALUES = "'presidential', 'governorship', 'senatorial', 'federal_constituency', 'chairmanship', 'national', 'state', 'lga', 'ward', 'pu'";
    private const LEGACY_VALUES = "'state', 'lga', 'ward', 'pu'";

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE system_settings MODIFY package ENUM(".self::COMPATIBLE_VALUES.") NULL DEFAULT NULL");

        DB::table('system_settings')->where('package', 'national')->update(['package' => 'presidential']);
        DB::table('system_settings')->where('package', 'state')->update(['package' => 'governorship']);
        DB::table('system_settings')->whereIn('package', ['lga', 'ward', 'pu'])->update(['package' => 'chairmanship']);

        DB::statement("ALTER TABLE system_settings MODIFY package ENUM(".self::COMMERCIAL_VALUES.") NULL DEFAULT 'presidential'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE system_settings MODIFY package ENUM(".self::COMPATIBLE_VALUES.") NULL DEFAULT NULL");

        DB::table('system_settings')->where('package', 'governorship')->update(['package' => 'state']);
        DB::table('system_settings')->where('package', 'chairmanship')->update(['package' => 'lga']);
        DB::table('system_settings')->whereIn('package', ['presidential', 'senatorial', 'federal_constituency'])->update(['package' => 'state']);

        DB::statement("ALTER TABLE system_settings MODIFY package ENUM(".self::LEGACY_VALUES.") NULL DEFAULT 'national'");
    }
};
