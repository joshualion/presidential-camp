<?php

use App\Services\DeploymentRegistrationService;
use App\Services\PackageScopeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaign_enterprise_legacy_access')) {
            Schema::create('campaign_enterprise_legacy_access', function (Blueprint $table) {
                $table->string('feature', 60)->primary();
                $table->boolean('eligible')->default(false);
                $table->string('domain')->nullable();
                $table->string('package_type', 40)->nullable();
                $table->string('installation_id', 64)->nullable();
                $table->unsignedInteger('provider_accounts')->default(0);
                $table->string('policy', 60);
                $table->timestamp('captured_at');
            });
        }
        // Persist a negative decision too. Configuring a provider after a fresh
        // installation must never turn into an unpaid legacy grant on a retry.
        if (DB::table('campaign_enterprise_legacy_access')->where('feature', 'sms_messaging')->exists()) {
            return;
        }
        $domain = null;
        $package = null;
        $installation = null;
        $count = 0;
        try {
            $scope = app(PackageScopeService::class)->current();
            $package = $scope->package_type === 'chairmanship' ? 'lga' : $scope->package_type;
            $domain = app(DeploymentRegistrationService::class)->domain();
            $state = app(DeploymentRegistrationService::class)->summary();
            $installation = ! empty($state['registered']) ? ($state['id'] ?? null) : null;
            if (! $scope->fallback_used && parse_url(config('app.url'), PHP_URL_SCHEME) === 'https'
                && in_array($package, ['presidential', 'governorship', 'senatorial', 'federal_constituency', 'lga'], true)
                && Schema::hasTable('sms_provider_accounts') && Schema::hasTable('system_settings')
                && DB::table('system_settings')->where('direct_sms_enabled', true)->exists()) {
                $count = DB::table('sms_provider_accounts')->where('is_active', true)
                    ->whereIn('provider', ['termii', 'bulksmsnigeria'])->whereNotNull('api_key')->where('api_key', '<>', '')
                    ->whereIn('user_id', DB::table('users')->where('status', 'active')->select('id'))->count();
            }
        } catch (\Throwable) {
            // Malformed identity is never evidence of legitimate prior access.
            $count = 0;
        }
        DB::table('campaign_enterprise_legacy_access')->insertOrIgnore([
            'feature' => 'sms_messaging', 'eligible' => $count > 0, 'domain' => $domain,
            'package_type' => $package, 'installation_id' => $installation,
            'provider_accounts' => $count, 'policy' => 'configured-direct-provider-v125', 'captured_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Legacy SMS access decisions are retained; restore a reviewed backup instead.');
    }
};
