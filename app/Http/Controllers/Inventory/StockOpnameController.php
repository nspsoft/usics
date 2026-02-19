<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockOpname::with(['warehouse', 'createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('opname_number', 'like', "%{$search}%");
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
            $query->join('warehouses', 'inv_stock_opnames.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('inv_stock_opnames.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $opnames = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Opname/Index', [
            'opnames' => $opnames,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'warehouse_id', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Opname/Form', [
            'opname' => null,
            'opnameNumber' => StockOpname::generateNumber(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_number' => 'required|string|max:30|unique:inv_stock_opnames,opname_number',
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => $validated['opname_number'],
            'warehouse_id' => $validated['warehouse_id'],
            'opname_date' => $validated['opname_date'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.opname.show', $opname)
            ->with('success', 'Stock Opname session created.');
    }

    public function show(StockOpname $opname): Response
    {
        $opname->load(['warehouse', 'createdBy', 'items.product']);

        return Inertia::render('Inventory/Opname/Show', [
            'opname' => array_merge($opname->toArray(), [
                'created_by_user' => $opname->createdBy ? [
                    'id' => $opname->createdBy->id,
                    'name' => $opname->createdBy->name,
                ] : null,
            ]),
        ]);
    }

    public function updateItems(Request $request, StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Cannot update completed opname.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:inv_stock_opname_items,id',
            'items.*.qty_physic' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $opname) {
            foreach ($validated['items'] as $itemData) {
                // Determine diff
                // We need to fetch original item to compare
                $item = $opname->items()->find($itemData['id']);
                if ($item) {
                    $item->update([
                        'qty_physic' => $itemData['qty_physic'],
                        'qty_difference' => $itemData['qty_physic'] - $item->qty_system,
                    ]);
                }
            }
            
            if ($opname->status === 'draft') {
                $opname->update(['status' => 'in_progress']);
            }
        });

        return back()->with('success', 'Counts saved successfully.');
    }

    /**
     * Auto-save a single item count (for mobile UI)
     */
    public function updateSingleItem(Request $request, StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return response()->json(['error' => 'Cannot update completed opname.'], 422);
        }

        $validated = $request->validate([
            'item_id'    => 'required|exists:inv_stock_opname_items,id',
            'qty_physic' => 'required|numeric|min:0',
        ]);

        $item = $opname->items()->find($validated['item_id']);
        if (!$item) {
            return response()->json(['error' => 'Item not found.'], 404);
        }

        $item->update([
            'qty_physic'     => $validated['qty_physic'],
            'qty_difference' => $validated['qty_physic'] - $item->qty_system,
        ]);

        if ($opname->status === 'draft') {
            $opname->update(['status' => 'in_progress']);
        }

        // Return progress stats
        $total   = $opname->items()->count();
        $counted = $opname->items()->whereColumn('qty_physic', '!=', 'qty_system')->count();

        return response()->json([
            'success'    => true,
            'difference' => $validated['qty_physic'] - $item->qty_system,
            'progress'   => [
                'total'   => $total,
                'counted' => $counted,
                'percent' => $total > 0 ? round(($counted / $total) * 100, 1) : 0,
            ],
        ]);
    }

    public function populate(StockOpname $opname)
    {
        if ($opname->items()->exists()) {
            return back()->with('error', 'Items already populated.');
        }

        $count = 0;

        // Use chunking to avoid memory issues with large product catalogs
        Product::active()
            ->stockManaged()
            ->select('id')
            ->chunk(500, function ($products) use ($opname, &$count) {
                $items = [];
                foreach ($products as $product) {
                    $qtySystem = ProductStock::where('product_id', $product->id)
                        ->where('warehouse_id', $opname->warehouse_id)
                        ->value('qty_on_hand') ?? 0;

                    $items[] = [
                        'stock_opname_id' => $opname->id,
                        'product_id' => $product->id,
                        'qty_system' => $qtySystem,
                        'qty_physic' => $qtySystem,
                        'qty_difference' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($items) > 0) {
                    DB::table('inv_stock_opname_items')->insert($items);
                    $count += count($items);
                }
            });

        return back()->with('success', "Populated {$count} items from system stock.");
    }

    public function complete(StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Already completed.');
        }

        DB::transaction(function () use ($opname) {
            foreach ($opname->items as $item) {
                $delta = $item->qty_physic - $item->qty_system;

                if ($delta != 0) {
                    $stock = ProductStock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'warehouse_id' => $opname->warehouse_id,
                        ],
                        [
                            'qty_on_hand' => 0,
                            'qty_reserved' => 0,
                            'qty_incoming' => 0,
                            'qty_outgoing' => 0,
                            'avg_cost' => 0,
                        ]
                    );

                    $stock->adjustStock(
                        $delta,
                        null,
                        StockMovement::TYPE_OPNAME,
                        $opname,
                        "Stock Opname #{$opname->opname_number}"
                    );
                }
            }

            $opname->update(['status' => 'completed']);
        });

        return back()->with('success', 'Stock Opname completed and adjustments posted.');
    }

    public function destroy(StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Cannot delete completed opname.');
        }

        $opname->delete();

        return redirect()->route('inventory.opname.index')
            ->with('success', 'Stock Opname deleted.');
    }
}
