<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    public function getStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $stock = ProductStock::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        return response()->json([
            'qty' => $stock ? $stock->qty_on_hand : 0
        ]);
    }
    public function index(Request $request): Response
    {
        $query = StockAdjustment::with(['warehouse', 'createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('adjustment_number', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('warehouse_id', $warehouse);
            });

        // Dynamic Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'warehouse_name') {
            $query->join('warehouses', 'inv_stock_adjustments.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('inv_stock_adjustments.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $adjustments = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Adjustments/Index', [
            'adjustments' => $adjustments,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'warehouse_id', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Adjustments/Form', [
            'adjustment' => null,
            'adjustmentNumber' => StockAdjustment::generateNumber(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->stockManaged()->select('id','sku','name','unit_id')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjustment_number' => 'required|string|max:30|unique:inv_stock_adjustments,adjustment_number',
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_date' => 'required|date',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_actual' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $validated['adjustment_number'],
                'warehouse_id' => $validated['warehouse_id'],
                'adjustment_date' => $validated['adjustment_date'],
                'status' => 'draft',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Get current system stock
                $stock = ProductStock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $validated['warehouse_id'])
                    ->first();
                
                $qtySystem = $stock ? $stock->qty_on_hand : 0;
                $difference = $item['qty_actual'] - $qtySystem;

                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'qty_system' => $qtySystem,
                    'qty_actual' => $item['qty_actual'],
                    'qty_difference' => $difference,
                ]);
            }
        });

        return redirect()->route('inventory.adjustments.index')
            ->with('success', 'Stock Adjustment draft created.');
    }

    public function show(StockAdjustment $adjustment): Response
    {
        $adjustment->load(['warehouse', 'items.product', 'createdBy']);

        return Inertia::render('Inventory/Adjustments/Show', [
            'adjustment' => $adjustment,
        ]);
    }

    public function complete(StockAdjustment $adjustment)
    {
        if ($adjustment->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be completed.');
        }

        DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->items as $item) {
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $adjustment->warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                // Recalculate difference based on CURRENT stock at moment of posting
                // This ensures "Actual" means "Make it this amount"
                $currentQty = $stock->qty_on_hand;
                $delta = $item->qty_actual - $currentQty;

                if ($delta != 0) {
                    $stock->adjustStock(
                        $delta,
                        null,
                        StockMovement::TYPE_ADJUSTMENT,
                        $adjustment,
                        "Adjustment #{$adjustment->adjustment_number}"
                    );
                    
                    // Update the item record with final values used
                    $item->update([
                        'qty_system' => $currentQty,
                        'qty_difference' => $delta,
                    ]);
                }
            }

            $adjustment->update(['status' => 'completed']);
        });

        return back()->with('success', 'Stock Adjustment posted successfully.');
    }

    public function destroy(StockAdjustment $adjustment)
    {
        if ($adjustment->status !== 'draft') {
            return back()->with('error', 'Only draft adjustments can be deleted.');
        }

        $adjustment->delete();

        return redirect()->route('inventory.adjustments.index')
            ->with('success', 'Stock Adjustment deleted.');
    }
}
