<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StockMovementController extends Controller
{
    /**
     * Reset stock movements and zero out inventory
     */
    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Invalid password. Cannot reset data.');
        }

        try {
            DB::transaction(function () {
                // 1. Truncate stock movements history
                DB::table('inv_stock_movements')->truncate();

                // 2. Reset all stock quantities to 0
                // We update widely to ensure all warehouses/products are reset
                DB::table('inv_product_stocks')->update([
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 0,
                    'updated_at' => now(),
                ]);
            });

            Log::warning('Stock Movements reset by user ' . auth()->id());

            return back()->with('success', 'Stock transactions history cleared and inventory reset to 0.');

        } catch (\Exception $e) {
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request): Response
    {
        $query = StockMovement::with(['product', 'warehouse', 'createdBy'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('warehouse_id', $warehouse);
            });

        // Dynamic Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'product_name') {
            $query->join('products', 'stock_movements.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction)
                  ->select('stock_movements.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('stock_movements.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $movements = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Movements/Index', [
            'movements' => $movements,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'type', 'warehouse_id', 'sort', 'direction']),
            'types' => [
                ['value' => 'adjustment', 'label' => 'Adjustment'],
                ['value' => 'po_receive', 'label' => 'PO Receive'],
                ['value' => 'so_delivery', 'label' => 'SO Delivery'],
                ['value' => 'production_in', 'label' => 'Production In'],
                ['value' => 'production_out', 'label' => 'Production Out'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ]);
    }
}
