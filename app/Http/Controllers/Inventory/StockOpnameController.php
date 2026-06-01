<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Exports\StockOpnameItemsExport;
use App\Imports\StockOpnamesFromExportImport;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

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

    public function export(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|string|in:draft,in_progress,completed,cancelled',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'opname_ids' => 'nullable|array',
            'opname_ids.*' => 'integer|exists:inv_stock_opnames,id',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = $dateFrom;
        }

        $filters = array_filter([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $validated['status'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'opname_ids' => !empty($validated['opname_ids'] ?? null) ? array_values(array_unique($validated['opname_ids'])) : null,
        ], fn ($v) => $v !== null && $v !== '');

        $suffix = now()->format('Y-m-d_His');
        if (!empty($filters['opname_ids'])) {
            $suffix = 'selected_' . now()->format('Y-m-d_His');
        }
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $suffix = $filters['date_from'] . '_to_' . $filters['date_to'];
        }

        return Excel::download(new StockOpnameItemsExport($filters), "stock_opname_export_{$suffix}.xlsx");
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'overwrite_existing' => 'nullable|boolean',
        ]);

        $import = new StockOpnamesFromExportImport((bool) ($validated['overwrite_existing'] ?? true));
        Excel::import($import, $validated['file']);

        $processedSessions = $import->createdCount + $import->updatedCount;
        if ($processedSessions > 0) {
            $msg = "Import berhasil. {$processedSessions} sesi diproses (baru: {$import->createdCount}, update: {$import->updatedCount}).";
            if (!empty($import->errors)) {
                $msg .= ' (Sebagian baris error)';
            }

            return redirect()
                ->route('inventory.opname.index')
                ->with('success', $msg)
                ->with('import_errors', $import->errors);
        }

        $msg = 'Import gagal: ' . (empty($import->errors) ? 'Tidak ada data valid.' : implode('; ', array_slice($import->errors, 0, 5)));
        return back()->with('error', $msg)->with('import_errors', $import->errors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_number' => 'required|string|max:30|unique:inv_stock_opnames,opname_number',
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'location' => 'required|string|max:100',
            'count_mode' => 'required|string|in:full_input,partial_input',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => $validated['opname_number'],
            'warehouse_id' => $validated['warehouse_id'],
            'opname_date' => $validated['opname_date'],
            'location' => $validated['location'],
            'count_mode' => $validated['count_mode'],
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

    public function addItem(Request $request, StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return response()->json(['error' => 'Cannot update completed opname.'], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_physic' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $qtySystem = (float) (ProductStock::where('product_id', $validated['product_id'])
            ->where('warehouse_id', $opname->warehouse_id)
            ->sum('qty_on_hand') ?? 0);

        $item = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($item) {
            $nextQtyPhysic = (float) $item->qty_physic + (float) $validated['qty_physic'];
            $nextNotes = $item->notes;
            if (!empty($validated['notes'])) {
                $nextNotes = $nextNotes ? trim($nextNotes) . "\n" . trim($validated['notes']) : trim($validated['notes']);
            }

            $item->update([
                'qty_system' => $qtySystem,
                'qty_physic' => $nextQtyPhysic,
                'qty_difference' => $nextQtyPhysic - $qtySystem,
                'notes' => $nextNotes,
            ]);
        } else {
            $item = StockOpnameItem::create([
                'stock_opname_id' => $opname->id,
                'product_id' => $validated['product_id'],
                'qty_system' => $qtySystem,
                'qty_physic' => $validated['qty_physic'],
                'qty_difference' => $validated['qty_physic'] - $qtySystem,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        $item->load('product');

        if ($opname->status === 'draft') {
            $opname->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'product' => $item->product,
                'qty_system' => (float) $item->qty_system,
                'qty_physic' => (float) $item->qty_physic,
                'qty_difference' => (float) $item->qty_difference,
                'notes' => $item->notes,
            ],
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
                    $qtySystem = (float) (ProductStock::where('product_id', $product->id)
                        ->where('warehouse_id', $opname->warehouse_id)
                        ->sum('qty_on_hand') ?? 0);

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

        try {
            DB::transaction(function () use ($opname) {
                $opname->load('items');

                if ($opname->count_mode === 'full_input') {
                    if ($opname->items->count() === 0) {
                        throw new \RuntimeException('Tidak ada item yang diinput. Tidak bisa complete untuk mode full count.');
                    }

                    $existingByProductId = $opname->items->keyBy('product_id');
                    $stocks = ProductStock::where('warehouse_id', $opname->warehouse_id)
                        ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
                        ->groupBy('product_id')
                        ->havingRaw('SUM(qty_on_hand) != 0')
                        ->get();

                    foreach ($stocks as $stockRow) {
                        $productId = (int) $stockRow->product_id;
                        $qtySystem = (float) $stockRow->qty_on_hand;

                        if (isset($existingByProductId[$productId])) {
                            $item = $existingByProductId[$productId];
                            $item->update([
                                'qty_system' => $qtySystem,
                                'qty_difference' => (float) $item->qty_physic - $qtySystem,
                            ]);
                        } else {
                            StockOpnameItem::create([
                                'stock_opname_id' => $opname->id,
                                'product_id' => $productId,
                                'qty_system' => $qtySystem,
                                'qty_physic' => 0,
                                'qty_difference' => -$qtySystem,
                                'notes' => 'AUTO: tidak diinput (dianggap 0)',
                            ]);
                        }
                    }

                    foreach ($opname->items as $item) {
                        if (!$stocks->firstWhere('product_id', $item->product_id)) {
                            $item->update([
                                'qty_system' => 0,
                                'qty_difference' => (float) $item->qty_physic,
                            ]);
                        }
                    }
                }

                $opname->refresh();
                $opname->load('items');

                foreach ($opname->items as $item) {
                    $delta = (float) $item->qty_physic - (float) $item->qty_system;

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
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

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

    public function locationStock(Request $request, StockOpname $opname)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30',
        ]);

        $location = Location::where('warehouse_id', $opname->warehouse_id)
            ->where('code', $validated['code'])
            ->first();

        if (!$location) {
            return response()->json(['message' => 'Lokasi tidak ditemukan.'], 404);
        }

        $productIds = ProductStock::where('warehouse_id', $opname->warehouse_id)
            ->where('location_id', $location->id)
            ->where('qty_on_hand', '!=', 0)
            ->pluck('product_id')
            ->values();

        return response()->json([
            'location' => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
            ],
            'product_ids' => $productIds,
        ]);
    }
}
