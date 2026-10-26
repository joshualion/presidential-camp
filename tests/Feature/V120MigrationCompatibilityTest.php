<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

test('v120 migrations preserve populated MyISAM tables and custom assignments', function () {
    $original = config('database.default');
    $configuration = config('database.connections.'.$original);
    $prefix = 'v120_'.bin2hex(random_bytes(4)).'_';
    $configuration['prefix'] = $prefix;
    config(['database.connections.v120_migration_test' => $configuration, 'database.default' => 'v120_migration_test']);
    $tables = ['campaign_default_roles', 'campaign_permission_restrictions', 'model_has_roles',
        'picture_evidences', 'video_evidences', 'election_incidents', 'system_settings', 'permissions', 'roles', 'users'];
    try {
        foreach (['users', 'roles'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->engine = 'MyISAM'; $blueprint->id(); $blueprint->string('name')->nullable();
            });
        }
        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'MyISAM'; $table->id(); $table->string('name', 100); $table->string('guard_name', 100);
            $table->string('group_name')->default('user'); $table->timestamps(); $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->engine = 'MyISAM'; $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('system_settings', function (Blueprint $table) { $table->engine = 'MyISAM'; $table->id(); });
        Schema::create('election_incidents', function (Blueprint $table) { $table->engine = 'MyISAM'; $table->id(); $table->string('remarks')->nullable(); });
        foreach (['picture_evidences', 'video_evidences'] as $name) {
            Schema::create($name, function (Blueprint $table) { $table->engine = 'MyISAM'; $table->id(); $table->string('file_path'); });
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'Existing user']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'Customer role']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => 1]);
        DB::table('permissions')->insert(['name' => 'customer.existing', 'guard_name' => 'web', 'group_name' => 'user']);
        DB::table('election_incidents')->insert(['id' => 1, 'remarks' => 'Historical incident']);
        DB::table('picture_evidences')->insert(['id' => 1, 'file_path' => 'evidences/pictures/historical.jpg']);
        foreach (['2026_10_25_000001_add_managed_campaign_capabilities.php', '2026_10_25_000002_add_incident_evidence_metadata.php'] as $file) {
            $migration = require database_path('migrations/'.$file); $migration->up(); $migration->up();
        }
        expect(DB::table('roles')->where('id', 1)->value('name'))->toBe('Customer role')
            ->and((bool) DB::table('users')->where('id', 1)->value('capabilities_managed'))->toBeFalse()
            ->and(DB::table('model_has_roles')->where('model_id', 1)->value('role_id'))->toBe(1)
            ->and(DB::table('permissions')->where('name', 'customer.existing')->exists())->toBeTrue()
            ->and(DB::table('picture_evidences')->where('id', 1)->value('file_path'))->toBe('evidences/pictures/historical.jpg')
            ->and(DB::table('election_incidents')->where('id', 1)->value('remarks'))->toBe('Historical incident');
        foreach (['users', 'roles', 'permissions', 'election_incidents', 'picture_evidences', 'video_evidences'] as $table) {
            $status = DB::selectOne('SHOW TABLE STATUS WHERE Name = ?', [$prefix.$table]);
            expect(strtolower($status->Engine))->toBe('myisam');
        }
    } finally {
        foreach ($tables as $table) { Schema::dropIfExists($table); }
        DB::purge('v120_migration_test'); config(['database.default' => $original]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
})->skip(fn () => DB::getDriverName() !== 'mysql', 'MySQL/MariaDB engine compatibility is covered by the database CI jobs.');
