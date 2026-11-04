<?php

namespace App\Services\Enterprise;

use App\Models\User;
use App\Models\PollingUnit;
use App\Services\CampaignCapabilityService;
use App\Services\LocationScopeService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Internal campaign materials only. No storefront orders, money or provider payouts. */
class CampaignInventoryService
{
    public function __construct(private LocationScopeService $scope, private CampaignCapabilityService $capabilities, private EntitlementService $entitlements) {}

    public function authorize(User $actor, string $permission): void
    {
        abort_unless($this->capabilities->allows($actor, $permission), 403, 'Permission not assigned.');
        abort_unless($this->entitlements->active('campaign_merchandise'), 402, 'An active Merchandise licence is required.');
    }

    public function units(User $actor): Builder
    {
        $query = PollingUnit::query();
        $this->scope->applyScope($query, $actor, 'polling_units');
        return $query->toBase();
    }

    private function assertUnit(User $actor, int $id): void
    {
        abort_unless($this->units($actor)->where('polling_units.id', $id)->exists(), 403, 'Campaign office is outside your jurisdiction.');
    }

    public function stocks(User $actor): Builder
    {
        $this->authorize($actor, 'merchandise.view');
        return DB::table('campaign_material_stocks as stock')
            ->join('campaign_materials as material', 'material.id', '=', 'stock.material_id')
            ->whereIn('stock.polling_unit_id', $this->units($actor)->select('polling_units.id'))
            ->select('stock.*', 'material.name', 'material.sku', 'material.unit', 'material.low_stock_level');
    }

    public function requests(User $actor): Builder
    {
        $this->authorize($actor, 'merchandise.orders.view');
        return DB::table('campaign_material_requests as requests')
            ->join('campaign_materials as material', 'material.id', '=', 'requests.material_id')
            ->whereIn('requests.source_polling_unit_id', $this->units($actor)->select('polling_units.id'))
            ->whereIn('requests.destination_polling_unit_id', $this->units($actor)->select('polling_units.id'))
            ->select('requests.*', 'material.name', 'material.sku');
    }

    public function movements(User $actor): Builder
    {
        $this->authorize($actor, 'merchandise.view');
        return DB::table('campaign_material_movements as movement')
            ->join('campaign_material_stocks as stock', 'stock.id', '=', 'movement.stock_id')
            ->join('campaign_materials as material', 'material.id', '=', 'stock.material_id')
            ->whereIn('stock.polling_unit_id', $this->units($actor)->select('polling_units.id'))
            ->select('movement.*', 'stock.polling_unit_id', 'material.sku', 'material.name');
    }

    public function category(User $actor, array $data): int
    {
        $this->authorize($actor, 'merchandise.categories.manage');
        $data = Validator::make($data, ['name' => 'required|string|max:120|unique:campaign_material_categories,name'])->validate();
        return DB::table('campaign_material_categories')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
    }

    public function material(User $actor, array $data): int
    {
        $this->authorize($actor, 'merchandise.products.manage');
        $data = Validator::make($data, [
            'category_id' => 'required|integer|exists:campaign_material_categories,id',
            'sku' => 'required|string|max:60|unique:campaign_materials,sku', 'name' => 'required|string|max:160',
            'unit' => 'required|string|max:30', 'low_stock_level' => 'required|integer|min:0|max:1000000000',
        ])->validate();
        return DB::table('campaign_materials')->insertGetId($data + ['created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function lockStock(int $material, int $unit): object
    {
        DB::table('campaign_material_stocks')->insertOrIgnore([
            'material_id' => $material, 'polling_unit_id' => $unit, 'quantity' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('campaign_material_stocks')->where('material_id', $material)->where('polling_unit_id', $unit)->lockForUpdate()->first();
    }

    private function movement(object $stock, int $change, User $actor, string $reason, string $type, string $reference, ?int $request = null): void
    {
        $balance = (int) $stock->quantity + $change;
        if ($balance < 0 || $balance > 1000000000) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock or stock limit exceeded.']);
        }
        DB::table('campaign_material_stocks')->where('id', $stock->id)->update(['quantity' => $balance, 'updated_at' => now()]);
        DB::table('campaign_material_movements')->insert([
            'stock_id' => $stock->id, 'reference' => $reference, 'type' => $type, 'change' => $change,
            'balance_after' => $balance, 'actor_id' => $actor->id, 'request_id' => $request, 'reason' => $reason, 'created_at' => now(),
        ]);
    }

    public function adjust(User $actor, array $data): void
    {
        $this->authorize($actor, 'merchandise.inventory.manage');
        $data = Validator::make($data, [
            'material_id' => 'required|integer|exists:campaign_materials,id', 'polling_unit_id' => 'required|integer',
            'quantity' => 'required|integer|not_in:0|min:-1000000000|max:1000000000',
            'reason' => 'required|string|max:500', 'reference' => 'required|uuid',
        ])->validate();
        $this->assertUnit($actor, (int) $data['polling_unit_id']);
        DB::transaction(function () use ($actor, $data) {
            $stock = $this->lockStock((int) $data['material_id'], (int) $data['polling_unit_id']);
            // Idempotent browser retry. A reference may never be reused to alter another operation.
            $prior = DB::table('campaign_material_movements')->where('reference', $data['reference'])->first();
            if ($prior) {
                abort_unless((int) $prior->stock_id === (int) $stock->id && (int) $prior->actor_id === $actor->id
                    && (int) $prior->change === (int) $data['quantity'] && $prior->reason === $data['reason'], 409, 'Stock reference was already used.');
                return;
            }
            $this->movement($stock, (int) $data['quantity'], $actor, $data['reason'], 'adjustment', $data['reference']);
        }, 3);
    }

    public function request(User $actor, array $data): int
    {
        $this->authorize($actor, 'merchandise.orders.view');
        $data = Validator::make($data, [
            'material_id' => 'required|integer|exists:campaign_materials,id',
            'source_polling_unit_id' => 'required|integer|different:destination_polling_unit_id',
            'destination_polling_unit_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:1000000000',
            'purpose' => 'required|string|max:500',
        ])->validate();
        $this->assertUnit($actor, (int) $data['source_polling_unit_id']);
        $this->assertUnit($actor, (int) $data['destination_polling_unit_id']);
        return DB::table('campaign_material_requests')->insertGetId($data + ['requested_by' => $actor->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function fulfil(User $actor, int $id): void
    {
        $this->authorize($actor, 'merchandise.orders.fulfil');
        DB::transaction(function () use ($actor, $id) {
            $request = DB::table('campaign_material_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($request, 404);
            $this->assertUnit($actor, (int) $request->source_polling_unit_id);
            $this->assertUnit($actor, (int) $request->destination_polling_unit_id);
            if ($request->status === 'fulfilled') { return; }
            abort_unless($request->status === 'pending', 409, 'This stock request cannot be fulfilled.');
            // Lock in stable location order to avoid opposite transfers deadlocking.
            $units = [(int) $request->source_polling_unit_id, (int) $request->destination_polling_unit_id];
            sort($units); $stocks = [];
            foreach ($units as $unit) { $stocks[$unit] = $this->lockStock((int) $request->material_id, $unit); }
            $this->movement($stocks[$request->source_polling_unit_id], -(int) $request->quantity, $actor,
                $request->purpose, 'distribution-out', (string) Str::uuid(), $id);
            $this->movement($stocks[$request->destination_polling_unit_id], (int) $request->quantity, $actor,
                $request->purpose, 'distribution-in', (string) Str::uuid(), $id);
            DB::table('campaign_material_requests')->where('id', $id)->update([
                'status' => 'fulfilled', 'fulfilled_by' => $actor->id, 'fulfilled_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }
}
