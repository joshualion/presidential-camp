<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampaignInventoryMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Production migrations retain audit data on down(); use a fresh disposable test schema,
        // then force the following application test to build its own schema rather than rolling back.
        $this->artisan('migrate:fresh')->assertSuccessful();
        $this->beforeApplicationDestroyed(function () { RefreshDatabaseState::$migrated = false; });
    }

    public function test_interrupted_inventory_schema_creation_resumes_and_preserves_stock_and_audit(): void
    {
        $now = now();
        $category = DB::table('campaign_material_categories')->insertGetId(['name' => 'Retained category', 'created_at' => $now, 'updated_at' => $now]);
        $material = DB::table('campaign_materials')->insertGetId(['category_id' => $category, 'sku' => 'RETAINED', 'name' => 'Retained materials', 'unit' => 'pieces', 'low_stock_level' => 5, 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $stock = DB::table('campaign_material_stocks')->insertGetId(['material_id' => $material, 'polling_unit_id' => 123, 'quantity' => 25, 'created_at' => $now, 'updated_at' => $now]);
        $request = DB::table('campaign_material_requests')->insertGetId(['material_id' => $material, 'source_polling_unit_id' => 123, 'destination_polling_unit_id' => 124, 'quantity' => 5, 'status' => 'pending', 'requested_by' => 1, 'purpose' => 'Retained request', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('campaign_material_request_events')->insert(['request_id' => $request, 'event' => 'requested', 'actor_id' => 1, 'reason' => 'Retained request', 'created_at' => $now]);
        // Simulate interruption immediately before the last table's DDL completed.
        Schema::drop('campaign_material_movements');
        $migration = require database_path('migrations/2026_11_04_125003_create_campaign_inventory.php');
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasTable('campaign_material_movements'));
        $this->assertSame(25, (int) DB::table('campaign_material_stocks')->where('id', $stock)->value('quantity'));
        $this->assertSame('Retained materials', DB::table('campaign_materials')->where('id', $material)->value('name'));
        $this->assertSame('requested', DB::table('campaign_material_request_events')->where('request_id', $request)->value('event'));
        DB::table('campaign_material_movements')->insert(['stock_id' => $stock, 'reference' => (string) Str::uuid(), 'type' => 'adjustment', 'change' => 25, 'balance_after' => 25, 'actor_id' => 1, 'reason' => 'Retained audit', 'created_at' => $now]);
        $migration->up();
        $this->assertSame(1, DB::table('campaign_material_movements')->count());
        $this->assertSame('Retained audit', DB::table('campaign_material_movements')->value('reason'));
    }
}
