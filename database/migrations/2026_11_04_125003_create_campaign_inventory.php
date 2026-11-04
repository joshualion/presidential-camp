<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_material_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->string('name', 120)->unique(); $table->timestamps();
        });
        Schema::create('campaign_materials', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('category_id')->constrained('campaign_material_categories');
            $table->string('sku', 60)->unique(); $table->string('name', 160);
            $table->string('unit', 30); $table->unsignedInteger('low_stock_level')->default(0);
            $table->unsignedBigInteger('created_by')->index(); $table->timestamps();
        });
        Schema::create('campaign_material_stocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('material_id')->constrained('campaign_materials');
            // External geography/user tables can still be MyISAM on older installations.
            $table->unsignedBigInteger('polling_unit_id')->index();
            $table->unsignedBigInteger('quantity')->default(0); $table->timestamps();
            $table->unique(['material_id', 'polling_unit_id']);
        });
        Schema::create('campaign_material_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('material_id')->constrained('campaign_materials');
            $table->unsignedBigInteger('source_polling_unit_id')->index();
            $table->unsignedBigInteger('destination_polling_unit_id')->index();
            $table->unsignedInteger('quantity'); $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('requested_by')->index(); $table->unsignedBigInteger('fulfilled_by')->nullable();
            $table->string('purpose', 500); $table->timestamp('fulfilled_at')->nullable(); $table->timestamps();
        });
        Schema::create('campaign_material_request_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('request_id')->constrained('campaign_material_requests');
            $table->string('event', 30); $table->unsignedBigInteger('actor_id')->index();
            $table->string('reason', 500); $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('campaign_material_movements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('stock_id')->constrained('campaign_material_stocks');
            $table->uuid('reference')->unique(); $table->string('type', 30); $table->bigInteger('change');
            $table->unsignedBigInteger('balance_after'); $table->unsignedBigInteger('actor_id')->index();
            $table->foreignId('request_id')->nullable()->constrained('campaign_material_requests');
            $table->string('reason', 500); $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Stock and movement history is retained. Removal requires an operator-reviewed data restoration plan.
    }
};
