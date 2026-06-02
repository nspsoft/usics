<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\SubcontractOrder;
use App\Models\WorkOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubcontractOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SubcontractOrder::with(['workOrder.product', 'supplier', 'purchaseOrder'])
            ->when($request->search, function ($q, $search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('workOrder', function ($wo) use ($search) {
                      $wo->where('wo_number', 'like', "%{$search}%");
                  });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderByDesc('created_at');

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Manufacturing/SubcontractOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'sent', 'label' => 'Sent'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ]
        ]);
    }

    public function create()
    {
        // To be implemented or handled via WO
    }

    public function store(Request $request)
    {
        // To be implemented 
    }

    public function show(SubcontractOrder $subcontractOrder): Response
    {
        $subcontractOrder->load([
            'workOrder.product',
            'workOrder.bom',
            'workOrder.components.product',
            'workOrder.warehouse',
            'workOrder.materialWarehouse',
            'supplier',
            'supplier.subcontractWarehouse',
            'purchaseOrder'
        ]);

        $stockMovements = StockMovement::where('reference_type', SubcontractOrder::class)
            ->where('reference_id', $subcontractOrder->id)
            ->with(['product', 'warehouse'])
            ->orderByDesc('created_at')
            ->get();

        $grReceipts = [];
        if ($subcontractOrder->purchase_order_id) {
            $fgProductId = $subcontractOrder->workOrder?->product_id;

            $grReceipts = GoodsReceipt::query()
                ->where('purchase_order_id', $subcontractOrder->purchase_order_id)
                ->where('status', GoodsReceipt::STATUS_COMPLETED)
                ->with(['items'])
                ->orderByDesc('receipt_date')
                ->get()
                ->map(function ($gr) use ($fgProductId) {
                    $qty = $fgProductId
                        ? (float) $gr->items->where('product_id', $fgProductId)->sum('qty_received')
                        : (float) $gr->items->sum('qty_received');

                    return [
                        'id' => $gr->id,
                        'grn_number' => $gr->grn_number,
                        'receipt_date' => $gr->receipt_date,
                        'status' => $gr->status,
                        'qty' => $qty,
                    ];
                })
                ->values()
                ->all();
        }

        $subcontractWarehouseId = $subcontractOrder->supplier?->subcontract_warehouse_id;
        $subcontractStocks = [];
        if ($subcontractWarehouseId) {
            $productIds = $subcontractOrder->workOrder?->components
                ?->pluck('product_id')
                ->unique()
                ->filter()
                ->values()
                ->all() ?? [];

            $stockByProductId = ProductStock::query()
                ->where('warehouse_id', $subcontractWarehouseId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            foreach ($subcontractOrder->workOrder?->components ?? [] as $component) {
                $subcontractStocks[] = [
                    'product_id' => $component->product_id,
                    'product' => $component->product,
                    'qty_sent' => (float) ($component->qty_consumed ?? 0),
                    'qty_on_hand' => (float) ($stockByProductId->get($component->product_id)?->qty_on_hand ?? 0),
                ];
            }
        }

        return Inertia::render('Manufacturing/SubcontractOrders/Show', [
            'order' => $subcontractOrder,
            'stockMovements' => $stockMovements,
            'subcontractWarehouse' => $subcontractOrder->supplier?->subcontractWarehouse,
            'subcontractStocks' => $subcontractStocks,
            'grReceipts' => $grReceipts,
        ]);
    }

    public function dispatchMaterials(Request $request, SubcontractOrder $subcontractOrder)
    {
        if (in_array($subcontractOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot dispatch materials for completed or cancelled orders.');
        }

        $workOrder = $subcontractOrder->workOrder;
        $materialWarehouseId = $workOrder->material_warehouse_id;
        if (!$materialWarehouseId) {
            $materialWarehouseId = \App\Models\Warehouse::query()
                ->where('code', 'WH-RM')
                ->orWhereRaw('LOWER(name) LIKE ?', ['%raw material%'])
                ->orderByRaw("CASE WHEN code = 'WH-RM' THEN 0 ELSE 1 END")
                ->value('id');
        }

        if (!$materialWarehouseId) {
            return back()->with('error', 'Material Warehouse (WH-RM) tidak ditemukan. Silakan set Material Warehouse di Work Order.');
        }

        $subcontWarehouseId = $subcontractOrder->supplier->subcontract_warehouse_id ?? null;
        if (!$subcontWarehouseId) {
            return back()->with('error', 'Subcontract Warehouse belum diset di Supplier. Silakan set Subcontract Warehouse terlebih dahulu agar sisa material di subcont bisa dipantau.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:work_order_components,id',
            'items.*.qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($subcontractOrder, $validated, $workOrder, $subcontWarehouseId, $materialWarehouseId) {
            foreach ($validated['items'] as $item) {
                if ($item['qty'] <= 0) continue;

                $component = $workOrder->components()->find($item['id']);
                if (!$component) continue;

                // Update actual stock balance and log movement
                $sourceStock = ProductStock::firstOrCreate([
                    'product_id' => $component->product_id,
                    'warehouse_id' => $materialWarehouseId,
                ], [
                    'qty_on_hand' => 0,
                    'avg_cost' => 0,
                ]);

                $sourceStock->adjustStock(
                    -$item['qty'],
                    null,
                    StockMovement::TYPE_TRANSFER,
                    $subcontractOrder,
                    "Transfer to Subcont WH for WO: {$workOrder->wo_number}",
                    'TO: ' . $subcontractOrder->supplier->name
                );

                $destStock = ProductStock::firstOrCreate([
                    'product_id' => $component->product_id,
                    'warehouse_id' => $subcontWarehouseId,
                ], [
                    'qty_on_hand' => 0,
                    'avg_cost' => $sourceStock->avg_cost,
                ]);

                $destStock->adjustStock(
                    $item['qty'],
                    $sourceStock->avg_cost,
                    StockMovement::TYPE_TRANSFER,
                    $subcontractOrder,
                    "Received from Main WH for WO: {$workOrder->wo_number}",
                    'FROM: WH-RM'
                );

                // Update component consumed qty (used as dispatched qty for subcontract)
                $component->increment('qty_consumed', $item['qty']);
            }

            if ($subcontractOrder->status === 'draft') {
                $subcontractOrder->update(['status' => 'sent']);
            }
        });

        return back()->with('success', 'Materials dispatched and stock updated.');
    }

    public function receiveGoods(Request $request, SubcontractOrder $subcontractOrder)
    {
        return back()->with('error', 'Transaksi subcontract mengikuti GR PO (Purchasing). Menu Receive Product (IN) dinonaktifkan untuk menghindari double input.');
    }

    public function edit(SubcontractOrder $subcontractOrder)
    {
        // To be implemented
    }

    public function update(Request $request, SubcontractOrder $subcontractOrder)
    {
        // To be implemented
    }

    public function destroy(SubcontractOrder $subcontractOrder)
    {
        // To be implemented
    }

    public function printDeliveryNote(SubcontractOrder $subcontractOrder)
    {
        $subcontractOrder->load([
            'workOrder.product',
            'workOrder.bom',
            'workOrder.components.product.unit',
            'supplier'
        ]);

        return view('print.subcontract-delivery-note', [
            'order' => $subcontractOrder
        ]);
    }

    public function printGrn(StockMovement $movement)
    {
        if ($movement->reference_type !== SubcontractOrder::class) {
            abort(404);
        }

        $movement->load(['product.unit', 'warehouse', 'reference.supplier', 'reference.workOrder.product']);

        return view('print.goods-receipt-note', [
            'movement' => $movement
        ]);
    }

    public function returnMaterials(Request $request, SubcontractOrder $subcontractOrder)
    {
        if (in_array($subcontractOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot return materials for completed or cancelled orders.');
        }

        $workOrder = $subcontractOrder->workOrder;
        $materialWarehouseId = $workOrder->material_warehouse_id;
        if (!$materialWarehouseId) {
            $materialWarehouseId = \App\Models\Warehouse::query()
                ->where('code', 'WH-RM')
                ->orWhereRaw('LOWER(name) LIKE ?', ['%raw material%'])
                ->orderByRaw("CASE WHEN code = 'WH-RM' THEN 0 ELSE 1 END")
                ->value('id');
        }

        if (!$materialWarehouseId) {
            return back()->with('error', 'Material Warehouse (WH-RM) tidak ditemukan. Silakan set Material Warehouse di Work Order.');
        }

        $subcontWarehouseId = $subcontractOrder->supplier->subcontract_warehouse_id ?? null;
        if (!$subcontWarehouseId) {
            return back()->with('error', 'Subcontract Warehouse belum diset di Supplier. Silakan set Subcontract Warehouse terlebih dahulu agar sisa material di subcont bisa dipantau.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:work_order_components,id',
            'items.*.qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($subcontractOrder, $validated, $workOrder, $subcontWarehouseId, $materialWarehouseId) {
            foreach ($validated['items'] as $item) {
                if ($item['qty'] <= 0) continue;

                $component = $workOrder->components()->find($item['id']);
                if (!$component) continue;

                // Update actual stock balance and log movement
                $subcontStock = ProductStock::firstOrCreate([
                    'product_id' => $component->product_id,
                    'warehouse_id' => $subcontWarehouseId,
                ], [
                    'qty_on_hand' => 0,
                    'avg_cost' => 0,
                ]);

                $subcontStock->adjustStock(
                    -$item['qty'],
                    null,
                    StockMovement::TYPE_TRANSFER,
                    $subcontractOrder,
                    "Returned unused material to Main WH for WO: {$workOrder->wo_number}",
                    'TO: WH-RM'
                );

                $mainStock = ProductStock::firstOrCreate([
                    'product_id' => $component->product_id,
                    'warehouse_id' => $materialWarehouseId,
                ], [
                    'qty_on_hand' => 0,
                    'avg_cost' => $subcontStock->avg_cost,
                ]);

                $mainStock->adjustStock(
                    $item['qty'],
                    $subcontStock->avg_cost,
                    StockMovement::TYPE_TRANSFER,
                    $subcontractOrder,
                    "Material returned from Subcontractor for WO: {$workOrder->wo_number}",
                    'FROM: ' . $subcontractOrder->supplier->name
                );

                // Decrease component consumed qty (as it is returned)
                $component->decrement('qty_consumed', $item['qty']);
            }
        });

        return back()->with('success', 'Unused materials returned to inventory.');
    }

    public function print(SubcontractOrder $subcontractOrder)
    {
        return view('print.subcontract-order', [
            'order' => $subcontractOrder->load(['workOrder.product.unit', 'workOrder.bom', 'workOrder.components.product.unit', 'supplier'])
        ]);
    }

    public function publicValidate($id)
    {
        $order = SubcontractOrder::with(['workOrder.product', 'supplier'])
            ->findOrFail($id);

        return view('print.public-subcontract-order-validation', [
            'order' => $order
        ]);
    }

    public function publicValidateSJ($id)
    {
        $order = SubcontractOrder::with(['workOrder.product', 'workOrder.components.product.unit', 'supplier'])
            ->findOrFail($id);

        return view('print.public-subcontract-sj-validation', [
            'order' => $order
        ]);
    }

    public function publicValidateGRN($id)
    {
        $movement = \App\Models\StockMovement::with(['product.unit', 'warehouse', 'reference.supplier', 'reference.workOrder.product'])
            ->findOrFail($id);
    
        return view('print.public-subcontract-grn-validation', [
            'movement' => $movement
        ]);
    }

    public function generatePurchaseOrder(SubcontractOrder $subcontractOrder)
    {
        if ($subcontractOrder->purchase_order_id) {
            return back()->with('error', 'Purchase Order already exists for this subcontract order.');
        }

        return DB::transaction(function () use ($subcontractOrder) {
            $workOrder = $subcontractOrder->workOrder;
            $supplier = \App\Models\Supplier::find($subcontractOrder->supplier_id);
            
            // 1. Create Purchase Order
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber($supplier, now()),
                'supplier_id' => $subcontractOrder->supplier_id,
                'warehouse_id' => $workOrder->warehouse_id,
                'order_date' => now(),
                'status' => 'draft',
                'notes' => "Service PO for Subcont Order: {$subcontractOrder->order_number} (WO: {$workOrder->wo_number})",
                'created_by' => auth()->id(),
            ]);

            // 2. Create PO Item (Service)
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $workOrder->product_id,
                'description' => "Subcontract Service: " . ($workOrder->product->name ?? 'Service'),
                'qty' => $workOrder->qty_planned,
                'unit_id' => $workOrder->product->unit_id ?? null,
                'unit_price' => $subcontractOrder->service_fee ?? 0,
                'discount_percent' => 0,
            ]);

            // 3. Link PO to Subcont Order
            $subcontractOrder->update([
                'purchase_order_id' => $po->id
            ]);

            return back()->with('success', 'Purchase Order Draft (Service) created successfully: ' . $po->po_number);
        });
    }
}
