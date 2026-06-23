<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Bom;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\PurchaseOrder;
use App\Models\WorkOrder;
use App\Models\StockReclassification;
use App\Services\DocumentNumberService;
use App\Services\InventoryIntelligenceService;
use App\Services\StockReclassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryIntelligenceController extends Controller
{
    protected InventoryIntelligenceService $service;

    public function __construct(InventoryIntelligenceService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the AI Stock Advisor Dashboard.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Stocks/Advisor', [
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Trigger the AI analysis.
     */
    public function analyze(Request $request)
    {
        $options = $request->only(['category_id']);
        
        try {
            $recommendations = $this->service->analyze($options);
            return response()->json($recommendations);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a Purchase Order Draft from the recommendation.
     */
    public function createPoDraft(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        $warehouseId = $validated['warehouse_id'] ?? Warehouse::active()->first()?->id;
        if (!$warehouseId) {
            return response()->json(['error' => 'Gudang aktif tidak ditemukan.'], 422);
        }

        try {
            $poId = DB::transaction(function () use ($validated, $warehouseId) {
                $supplier = Supplier::findOrFail($validated['supplier_id']);
                
                $poNumber = app(DocumentNumberService::class)->generate(
                    'purchase_order',
                    ['SUPP_CODE' => $supplier->code ?? ''],
                    date('Y-m-d')
                );

                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $validated['supplier_id'],
                    'warehouse_id' => $warehouseId,
                    'order_date' => date('Y-m-d'),
                    'status' => 'draft',
                    'tax_percent' => 11,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $prod = Product::find($item['product_id']);
                    $po->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $prod?->unit_id,
                        'unit_price' => $prod?->cost_price ?? 0,
                        'discount_percent' => 0,
                    ]);
                }

                return $po->id;
            });

            return response()->json([
                'success' => 'Draft Purchase Order berhasil dibuat.',
                'redirect_url' => route('purchasing.orders.edit', $poId)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a Work Order Draft from the recommendation.
     */
    public function createWoDraft(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_planned' => 'required|numeric|min:0.0001',
            'items.*.production_type' => 'required|in:internal,subcontract',
            'items.*.supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $warehouseId = Warehouse::active()->first()?->id;
        if (!$warehouseId) {
            return response()->json(['error' => 'Gudang aktif tidak ditemukan.'], 422);
        }

        try {
            $woIds = DB::transaction(function () use ($validated, $warehouseId) {
                $createdIds = [];
                foreach ($validated['items'] as $item) {
                    $bom = Bom::where('product_id', $item['product_id'])->where('is_active', true)->first();
                    if (!$bom) {
                        throw new \Exception("BOM aktif untuk produk ID " . $item['product_id'] . " tidak ditemukan.");
                    }

                    $wo = WorkOrder::create([
                        'company_id' => session('company_id'),
                        'wo_number' => WorkOrder::generateWoNumber(),
                        'bom_id' => $bom->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $warehouseId,
                        'material_warehouse_id' => $warehouseId,
                        'qty_planned' => $item['qty_planned'],
                        'planned_start' => date('Y-m-d'),
                        'planned_end' => date('Y-m-d', strtotime('+3 days')),
                        'status' => 'draft',
                        'priority' => 'normal',
                        'created_by' => auth()->id(),
                        'production_type' => $item['production_type'],
                        'supplier_id' => $item['supplier_id'] ?? null,
                    ]);

                    $wo->initializeFromBom();

                    if ($wo->production_type === 'subcontract') {
                        $orderNumber = 'SCO-' . date('Ymd') . '-' . str_pad($wo->id, 4, '0', STR_PAD_LEFT);
                        $wo->subcontractOrders()->create([
                            'supplier_id' => $wo->supplier_id,
                            'order_number' => $orderNumber,
                            'status' => 'draft',
                            'service_fee' => 0,
                        ]);
                    }
                    $createdIds[] = $wo->id;
                }
                return $createdIds;
            });

            $redirectUrl = count($woIds) === 1
                ? route('manufacturing.work-orders.edit', $woIds[0])
                : route('manufacturing.work-orders.index');

            return response()->json([
                'success' => count($woIds) . ' Draft Work Order berhasil dibuat.',
                'redirect_url' => $redirectUrl
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a Stock Reclassification Draft from the recommendation.
     */
    public function createReclassDraft(Request $request, StockReclassificationService $reclassService)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.source_product_id' => 'required|exists:products,id',
            'items.*.target_product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $warehouseId = $validated['warehouse_id'] ?? Warehouse::active()->first()?->id;
        if (!$warehouseId) {
            return response()->json(['error' => 'Gudang aktif tidak ditemukan.'], 422);
        }

        try {
            $reclassId = DB::transaction(function () use ($validated, $warehouseId, $reclassService) {
                $reclass = StockReclassification::create([
                    'reclass_number' => StockReclassification::generateNumber(),
                    'warehouse_id' => $warehouseId,
                    'reclass_date' => date('Y-m-d'),
                    'status' => StockReclassification::STATUS_DRAFT,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $prod = Product::find($item['source_product_id']);
                    $reclass->items()->create([
                        'source_product_id' => $item['source_product_id'],
                        'target_product_id' => $item['target_product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $prod?->unit_id,
                        'cost_per_unit' => $prod?->cost_price ?? 0,
                    ]);
                }

                $reclassService->syncDraftTotals($reclass);

                return $reclass->id;
            });

            return response()->json([
                'success' => 'Draft Stock Reclassification berhasil dibuat.',
                'redirect_url' => route('inventory.reclassifications.edit', $reclassId)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
