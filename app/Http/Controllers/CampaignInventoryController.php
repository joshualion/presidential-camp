<?php

namespace App\Http\Controllers;

use App\Services\Enterprise\CampaignInventoryService;
use App\Services\CampaignCapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignInventoryController extends Controller
{
    public function index(Request $request, CampaignInventoryService $inventory, CampaignCapabilityService $capabilities)
    {
        $actor = $request->user();
        $stocks = $inventory->stocks($actor)->orderBy('stock.id')->paginate(30, ['*'], 'stock_page');
        $can = fn (string $permission) => $capabilities->allows($actor, $permission);
        return response()->view('backend.enterprise.inventory', [
            'profileData' => $actor, 'pageTitle' => 'Campaign Merchandise & Inventory', 'stocks' => $stocks,
            'lowStock' => $inventory->stocks($actor)->whereColumn('stock.quantity', '<=', 'material.low_stock_level')->count(),
            'materials' => DB::table('campaign_materials')->orderBy('name')->limit(2000)->get(),
            'categories' => DB::table('campaign_material_categories')->orderBy('name')->get(),
            'requests' => $can('merchandise.orders.view') ? $inventory->requests($actor)->orderByDesc('requests.id')->paginate(20, ['*'], 'request_page') : null,
            'movements' => $inventory->movements($actor)->orderByDesc('movement.id')->paginate(30, ['*'], 'movement_page'),
            'can' => $can, 'reference' => (string) Str::uuid(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function locations(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->authorize($request->user(), 'merchandise.view');
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $query = $inventory->units($request->user());
        if ($search = trim($data['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                $query->where('polling_units.name', 'like', '%'.$search.'%');
                if (ctype_digit($search)) { $query->orWhere('polling_units.id', (int) $search); }
            });
        }
        return response()->json(['locations' => $query->orderBy('polling_units.id')->limit(25)->get(['polling_units.id', 'polling_units.name'])])
            ->header('Cache-Control', 'private, no-store');
    }

    public function category(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->category($request->user(), $request->all());
        return back()->with('inventory_status', 'Category created.');
    }

    public function material(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->material($request->user(), $request->all());
        return back()->with('inventory_status', 'Campaign material created.');
    }

    public function adjust(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->adjust($request->user(), $request->all());
        return back()->with('inventory_status', 'Stock movement recorded.');
    }

    public function requestStock(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->request($request->user(), $request->all());
        return back()->with('inventory_status', 'Distribution request submitted.');
    }

    public function fulfil(Request $request, CampaignInventoryService $inventory, int $id)
    {
        $inventory->fulfil($request->user(), $id);
        return back()->with('inventory_status', 'Distribution fulfilled and stock balances updated.');
    }

    public function export(Request $request, CampaignInventoryService $inventory)
    {
        $inventory->authorize($request->user(), 'merchandise.reports.export');
        $query = $inventory->stocks($request->user())->orderBy('stock.id');
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Material', 'Unit', 'Polling unit / campaign office ID', 'Stock', 'Low stock level'], ',', '"', '');
            foreach ($query->cursor() as $row) {
                $safe = fn ($value) => preg_match('/^[\s]*[=+\-@]/u', (string) $value) ? "'".$value : $value;
                fputcsv($out, array_map($safe, [$row->sku, $row->name, $row->unit, $row->polling_unit_id, $row->quantity, $row->low_stock_level]), ',', '"', '');
            }
            fclose($out);
        }, 'campaign-material-stock.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
