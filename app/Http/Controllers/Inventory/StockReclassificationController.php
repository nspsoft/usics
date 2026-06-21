<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductReclassMapping;
use App\Models\Product;
use App\Models\StockReclassification;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use App\Services\StockReclassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StockReclassificationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockReclassification::with(['warehouse', 'targetWarehouse', 'createdBy', 'postedBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('reclass_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->warehouse_id, function ($q, $warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->orderByDesc('created_at');

        return Inertia::render('Inventory/Reclassifications/Index', [
            'reclassifications' => $query->paginate(20)->withQueryString(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'warehouse_id']),
            'statuses' => [
                ['value' => StockReclassification::STATUS_DRAFT, 'label' => 'Draft'],
                ['value' => StockReclassification::STATUS_POSTED, 'label' => 'Posted'],
                ['value' => StockReclassification::STATUS_CANCELLED, 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        $mappingRows = ProductReclassMapping::active()
            ->get(['source_product_id', 'target_product_id', 'is_default']);

        $mappingTargetIds = $mappingRows
            ->pluck('target_product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $mappingTargetProducts = Product::query()
            ->whereIn('id', $mappingTargetIds)
            ->select('id', 'sku', 'name', 'unit_id', 'cost_price', 'selling_price')
            ->with(['unit:id,name,symbol'])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $unit = $p->unit?->symbol ?? $p->unit?->name ?? '-';
                return [
                    'id' => $p->id,
                    'label' => "{$p->name} | SKU: " . ($p->sku ?? '-') . " | Unit: {$unit}",
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit_id' => $p->unit_id,
                    'cost_price' => (float) $p->cost_price,
                    'selling_price' => (float) $p->selling_price,
                ];
            })
            ->values();

        return Inertia::render('Inventory/Reclassifications/Form', [
            'reclassification' => null,
            'reclassNumber' => StockReclassification::generateNumber(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'productLookupUrl' => route('inventory.products.lookup'),
            'mappingTargetProducts' => $mappingTargetProducts,
            'mappings' => $mappingRows,
        ]);
    }

    public function store(Request $request, StockReclassificationService $service)
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $service) {
            $reclassNumber = $validated['reclass_number'] ?: app(DocumentNumberService::class)->generate('stock_reclassification', [], $validated['reclass_date']);

            $reclassification = StockReclassification::create([
                'reclass_number' => $reclassNumber,
                'warehouse_id' => $validated['warehouse_id'],
                'target_warehouse_id' => $validated['target_warehouse_id'] ?? null,
                'reclass_date' => $validated['reclass_date'],
                'status' => StockReclassification::STATUS_DRAFT,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $reclassification->items()->create([
                    'source_product_id' => $item['source_product_id'],
                    'target_product_id' => $item['target_product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'qty' => $item['qty'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $service->syncDraftTotals($reclassification);
        });

        return redirect()->route('inventory.reclassifications.index')
            ->with('success', 'Draft reclass stock berhasil dibuat.');
    }

    public function show(StockReclassification $reclassification): Response
    {
        $reclassification->load([
            'warehouse',
            'targetWarehouse',
            'items.sourceProduct.unit',
            'items.targetProduct.unit',
            'items.unit',
            'createdBy',
            'postedBy',
        ]);

        return Inertia::render('Inventory/Reclassifications/Show', [
            'reclassification' => $reclassification,
        ]);
    }

    public function edit(StockReclassification $reclassification): Response
    {
        if ($reclassification->status !== StockReclassification::STATUS_DRAFT) {
            return redirect()->route('inventory.reclassifications.show', $reclassification)
                ->with('error', 'Hanya draft yang bisa diedit.');
        }

        $reclassification->load(['items.sourceProduct.unit', 'items.targetProduct.unit', 'items.unit']);

        $mappingRows = ProductReclassMapping::active()
            ->get(['source_product_id', 'target_product_id', 'is_default']);

        $mappingTargetIds = $mappingRows
            ->pluck('target_product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $mappingTargetProducts = Product::query()
            ->whereIn('id', $mappingTargetIds)
            ->select('id', 'sku', 'name', 'unit_id', 'cost_price', 'selling_price')
            ->with(['unit:id,name,symbol'])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $unit = $p->unit?->symbol ?? $p->unit?->name ?? '-';
                return [
                    'id' => $p->id,
                    'label' => "{$p->name} | SKU: " . ($p->sku ?? '-') . " | Unit: {$unit}",
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'unit_id' => $p->unit_id,
                    'cost_price' => (float) $p->cost_price,
                    'selling_price' => (float) $p->selling_price,
                ];
            })
            ->values();

        return Inertia::render('Inventory/Reclassifications/Form', [
            'reclassification' => $reclassification,
            'reclassNumber' => $reclassification->reclass_number,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'productLookupUrl' => route('inventory.products.lookup'),
            'mappingTargetProducts' => $mappingTargetProducts,
            'mappings' => $mappingRows,
        ]);
    }

    public function update(Request $request, StockReclassification $reclassification, StockReclassificationService $service)
    {
        if ($reclassification->status !== StockReclassification::STATUS_DRAFT) {
            return back()->with('error', 'Hanya draft yang bisa diubah.');
        }

        $validated = $this->validatePayload($request, $reclassification->id);

        DB::transaction(function () use ($validated, $reclassification, $service) {
            if ($validated['reclass_number'] !== $reclassification->reclass_number) {
                app(DocumentNumberService::class)->sync('stock_reclassification', $validated['reclass_number']);
            }

            $reclassification->update([
                'reclass_number' => $validated['reclass_number'],
                'warehouse_id' => $validated['warehouse_id'],
                'target_warehouse_id' => $validated['target_warehouse_id'] ?? null,
                'reclass_date' => $validated['reclass_date'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $reclassification->items()->delete();

            foreach ($validated['items'] as $item) {
                $reclassification->items()->create([
                    'source_product_id' => $item['source_product_id'],
                    'target_product_id' => $item['target_product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'qty' => $item['qty'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $service->syncDraftTotals($reclassification);
        });

        return redirect()->route('inventory.reclassifications.show', $reclassification)
            ->with('success', 'Draft reclass stock berhasil diupdate.');
    }

    public function post(StockReclassification $reclassification, StockReclassificationService $service)
    {
        try {
            $service->post($reclassification);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reclass stock berhasil diposting.');
    }

    public function destroy(StockReclassification $reclassification)
    {
        if ($reclassification->status !== StockReclassification::STATUS_DRAFT) {
            return back()->with('error', 'Hanya draft yang bisa dihapus.');
        }

        $reclassification->delete();

        return redirect()->route('inventory.reclassifications.index')
            ->with('success', 'Draft reclass stock berhasil dihapus.');
    }

    public function autoFill(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $warehouseId = $request->warehouse_id;

        $mappings = ProductReclassMapping::active()
            ->get()
            ->groupBy('source_product_id');

        $stocks = \App\Models\ProductStock::where('warehouse_id', $warehouseId)
            ->where('qty_on_hand', '>', 0)
            ->with(['product.unit'])
            ->get();

        $items = [];

        foreach ($stocks as $stock) {
            $productId = $stock->product_id;
            if ($mappings->has($productId)) {
                $productMappings = $mappings->get($productId);
                $mapping = $productMappings->firstWhere('is_default', true) ?? $productMappings->first();
                if ($mapping) {
                    $availableQty = (float) max(0, $stock->qty_on_hand - $stock->qty_reserved);
                    if ($availableQty > 0) {
                        $targetProduct = Product::with('unit')->find($mapping->target_product_id);
                        if ($targetProduct) {
                            $items[] = [
                                'source_product_id' => $productId,
                                'target_product_id' => $targetProduct->id,
                                'unit_id' => $targetProduct->unit_id,
                                'qty' => $availableQty,
                                'source_stock' => $availableQty,
                                'notes' => 'Otomatis reclass dari stok yang ada',
                                'source_product' => [
                                    'id' => $stock->product->id,
                                    'name' => $stock->product->name,
                                    'sku' => $stock->product->sku,
                                    'cost_price' => (float) $stock->product->cost_price,
                                    'selling_price' => (float) $stock->product->selling_price,
                                    'unit' => $stock->product->unit ? [
                                        'id' => $stock->product->unit->id,
                                        'name' => $stock->product->unit->name,
                                        'symbol' => $stock->product->unit->symbol,
                                    ] : null,
                                ],
                                'target_product' => [
                                    'id' => $targetProduct->id,
                                    'name' => $targetProduct->name,
                                    'sku' => $targetProduct->sku,
                                    'cost_price' => (float) $targetProduct->cost_price,
                                    'selling_price' => (float) $targetProduct->selling_price,
                                    'unit' => $targetProduct->unit ? [
                                        'id' => $targetProduct->unit->id,
                                        'name' => $targetProduct->unit->name,
                                        'symbol' => $targetProduct->unit->symbol,
                                    ] : null,
                                ],
                            ];
                        }
                    }
                }
            }
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function autoGenerate(Request $request, StockReclassificationService $service)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'target_warehouse_id' => 'nullable|exists:warehouses,id',
            'reclass_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $warehouseId = $validated['warehouse_id'];

        $mappings = ProductReclassMapping::active()
            ->with(['targetProduct'])
            ->get()
            ->groupBy('source_product_id');

        $stocks = \App\Models\ProductStock::where('warehouse_id', $warehouseId)
            ->where('qty_on_hand', '>', 0)
            ->with(['product'])
            ->get();

        $itemsToCreate = [];

        foreach ($stocks as $stock) {
            $productId = $stock->product_id;
            if ($mappings->has($productId)) {
                $productMappings = $mappings->get($productId);
                $mapping = $productMappings->firstWhere('is_default', true) ?? $productMappings->first();
                if ($mapping) {
                    $availableQty = (float) max(0, $stock->qty_on_hand - $stock->qty_reserved);
                    if ($availableQty > 0) {
                        $itemsToCreate[] = [
                            'source_product_id' => $productId,
                            'target_product_id' => $mapping->target_product_id,
                            'unit_id' => $mapping->targetProduct?->unit_id ?? $stock->product->unit_id,
                            'qty' => $availableQty,
                            'notes' => 'Otomatis reclass dari stok yang ada',
                        ];
                    }
                }
            }
        }

        if (empty($itemsToCreate)) {
            return back()->with('error', 'Tidak ada stok produk yang memiliki mapping aktif di gudang terpilih.');
        }

        $reclassification = DB::transaction(function () use ($validated, $itemsToCreate, $service) {
            $reclassNumber = app(DocumentNumberService::class)->generate('stock_reclassification', [], $validated['reclass_date']);

            $reclass = StockReclassification::create([
                'reclass_number' => $reclassNumber,
                'warehouse_id' => $validated['warehouse_id'],
                'target_warehouse_id' => $validated['target_warehouse_id'] ?? null,
                'reclass_date' => $validated['reclass_date'],
                'status' => StockReclassification::STATUS_DRAFT,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsToCreate as $item) {
                $reclass->items()->create($item);
            }

            $service->syncDraftTotals($reclass);

            return $reclass;
        });

        return redirect()->route('inventory.reclassifications.edit', $reclassification->id)
            ->with('success', 'Draft reclass otomatis berhasil dibuat berdasarkan mapping dan stock yang tersedia.');
    }

    public function destroyItem(\App\Models\StockReclassificationItem $item, StockReclassificationService $service)
    {
        $reclassification = $item->reclassification;

        if ($reclassification->status !== StockReclassification::STATUS_DRAFT) {
            return back()->with('error', 'Hanya item pada draft yang bisa dihapus.');
        }

        $item->delete();

        // Recalculate totals
        $service->syncDraftTotals($reclassification);

        return back()->with('success', 'Item berhasil dihapus dari reclass.');
    }

    protected function validatePayload(Request $request, ?int $id = null): array
    {
        $validator = Validator::make($request->all(), [
            'reclass_number' => 'required|string|max:30|unique:inv_stock_reclassifications,reclass_number,' . $id,
            'warehouse_id' => 'required|exists:warehouses,id',
            'target_warehouse_id' => 'nullable|exists:warehouses,id',
            'reclass_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.source_product_id' => 'required|exists:products,id',
            'items.*.target_product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.notes' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $mappingRows = ProductReclassMapping::active()
                ->get(['source_product_id', 'target_product_id', 'is_default'])
                ->groupBy('source_product_id');

            foreach ((array) $request->input('items', []) as $index => $item) {
                if (!empty($item['source_product_id']) && !empty($item['target_product_id']) && (string) $item['source_product_id'] === (string) $item['target_product_id']) {
                    $validator->errors()->add("items.{$index}.target_product_id", 'Source product dan target product tidak boleh sama.');
                }

                if (!empty($item['source_product_id']) && !empty($item['target_product_id'])) {
                    $allowedTargets = $mappingRows->get((int) $item['source_product_id'], collect())
                        ->pluck('target_product_id')
                        ->map(fn ($v) => (int) $v)
                        ->unique()
                        ->values()
                        ->all();

                    if (!empty($allowedTargets) && !in_array((int) $item['target_product_id'], $allowedTargets, true)) {
                        $validator->errors()->add("items.{$index}.target_product_id", 'Target product tidak sesuai mapping. Silakan periksa Reclass Mapping.');
                    }
                }
            }
        });

        return $validator->validate();
    }
}
