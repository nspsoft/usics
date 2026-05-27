<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $returns = PurchaseReturn::with(['supplier', 'warehouse', 'creator'])
            ->when($request->search, function($query, $search) {
                $query->where('number', 'like', "%{$search}%");
            })
            ->when($request->status, function($q, $status) {
                $q->where('status', $status);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $returns->join('suppliers', 'purchase_returns.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction)
                  ->select('purchase_returns.*');
        } elseif ($sort === 'warehouse_name') {
            $returns->join('warehouses', 'purchase_returns.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('purchase_returns.*');
        } elseif ($sort === 'po_number') {
            $returns->leftJoin('purchase_orders', 'purchase_returns.purchase_order_id', '=', 'purchase_orders.id')
                  ->orderBy('purchase_orders.po_number', $direction)
                  ->select('purchase_returns.*');
        } else {
            $returns->orderBy($sort, $direction);
        }

        $returns = $returns->paginate(20)->withQueryString();

        return Inertia::render('Purchasing/Returns/Index', [
            'returns' => $returns,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request): Response
    {
        $purchaseOrder = null;
        if ($request->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])
                ->find($request->purchase_order_id);
        }

        $prefill = null;

        if ($request->goods_receipt_id) {
            $receipt = GoodsReceipt::with([
                'purchaseOrder.goodsReceipts.items',
                'purchaseOrder.returns.items',
                'supplier',
                'warehouse',
                'items.product',
                'items.purchaseOrderItem',
            ])->findOrFail($request->goods_receipt_id);

            $purchaseOrder = $receipt->purchaseOrder ?? $purchaseOrder;

            $returnableByProduct = [];

            if ($purchaseOrder) {
                $receivedByProduct = $purchaseOrder->goodsReceipts
                    ->flatMap(fn ($gr) => $gr->items)
                    ->groupBy('product_id')
                    ->map(fn ($items) => (float) $items->sum('qty_received'));

                $returnedByProduct = $purchaseOrder->returns
                    ->flatMap(fn ($r) => $r->items)
                    ->groupBy('product_id')
                    ->map(fn ($items) => (float) $items->sum('qty'));

                foreach ($receivedByProduct as $productId => $receivedQty) {
                    $returnedQty = (float) ($returnedByProduct[$productId] ?? 0);
                    $returnableByProduct[$productId] = max(0, $receivedQty - $returnedQty);
                }
            }

            $items = [];
            foreach ($receipt->items as $grItem) {
                $product = $grItem->product;
                if (!$product) {
                    continue;
                }

                $defaultQty = (float) $grItem->qty_received;
                $productId = (int) $grItem->product_id;

                if (array_key_exists($productId, $returnableByProduct)) {
                    $allowed = (float) $returnableByProduct[$productId];
                    if ($allowed <= 0) {
                        continue;
                    }
                    $defaultQty = min($defaultQty, $allowed);
                    $returnableByProduct[$productId] = max(0, $allowed - $defaultQty);
                }

                if ($defaultQty <= 0) {
                    continue;
                }

                $unitPrice = (float) ($grItem->purchaseOrderItem?->unit_price ?? $grItem->unit_cost ?? 0);

                $items[] = [
                    'product_id' => $productId,
                    'name' => $product->name ?? 'Unknown',
                    'sku' => $product->sku ?? '-',
                    'qty' => $defaultQty,
                    'unit_price' => $unitPrice,
                ];
            }

            $prefill = [
                'goods_receipt_id' => $receipt->id,
                'grn_number' => $receipt->grn_number,
                'purchase_order_id' => $receipt->purchase_order_id,
                'supplier_id' => $receipt->supplier_id,
                'warehouse_id' => $receipt->warehouse_id,
                'reason' => "Correction for GRN {$receipt->grn_number}",
                'items' => $items,
            ];
        }

        return Inertia::render('Purchasing/Returns/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_PARTIAL])
                ->with('supplier')
                ->orderByDesc('created_at')
                ->get(),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku'])->each->setAppends([]),
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function() use ($request) {
            // Bug 5 fix: Use DB lock to prevent duplicate numbers under concurrency
            $lastReturn = PurchaseReturn::lockForUpdate()->orderBy('id', 'desc')->first();
            $number = 'PRT/' . date('Ymd') . '/' . str_pad(($lastReturn->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            $purchaseReturn = PurchaseReturn::create([
                'number' => $number,
                'purchase_order_id' => $request->purchase_order_id,
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'return_date' => $request->return_date,
                'reason' => $request->reason,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalPrice = $item['qty'] * $item['unit_price'];
                $purchaseReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                ]);
                $totalAmount += $totalPrice;
            }

            $purchaseReturn->update(['total_amount' => $totalAmount]);

            return redirect()->route('purchasing.purchase-returns.index')->with('success', 'Purchase return created successfully.');
        });
    }

    public function show(PurchaseReturn $return): Response
    {
        return Inertia::render('Purchasing/Returns/Show', [
            'purchaseReturn' => $return->load(['items.product', 'supplier', 'warehouse', 'creator', 'purchaseOrder']),
        ]);
    }

    public function confirm(PurchaseReturn $purchaseReturn)
    {
        if ($purchaseReturn->status !== 'draft') {
            return back()->with('error', 'Only draft returns can be confirmed.');
        }

        return DB::transaction(function() use ($purchaseReturn) {
            foreach ($purchaseReturn->items as $item) {
                $stock = ProductStock::firstOrCreate([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $purchaseReturn->warehouse_id,
                ], [
                    'qty_on_hand' => 0,
                ]);

                // Bug 4 fix: Warn if stock will go negative, but allow (soft block)
                if ($stock->qty_on_hand < $item->qty) {
                    \Illuminate\Support\Facades\Log::warning(
                        "Negative stock warning: Product #{$item->product_id} in Warehouse #{$purchaseReturn->warehouse_id}. " .
                        "Current: {$stock->qty_on_hand}, Returning: {$item->qty}. " .
                        "Return: {$purchaseReturn->number}, User: " . auth()->id()
                    );
                }

                // Reduce stock for purchase return
                $stock->adjustStock(
                    -$item->qty,
                    null,
                    StockMovement::TYPE_PURCHASE_RETURN,
                    $purchaseReturn,
                    "Purchase Return: {$purchaseReturn->number}"
                );

                // Update PO Item qty_returned
                $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $purchaseReturn->purchase_order_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                
                if ($poItem) {
                    $poItem->qty_returned += $item->qty;
                    $poItem->save();
                }
            }

            $purchaseReturn->update(['status' => 'confirmed']);

            activity()
                ->performedOn($purchaseReturn)
                ->causedBy(auth()->user())
                ->withProperties([
                    'number' => $purchaseReturn->number,
                    'purchase_order_id' => $purchaseReturn->purchase_order_id,
                    'supplier_id' => $purchaseReturn->supplier_id,
                    'warehouse_id' => $purchaseReturn->warehouse_id,
                    'items_count' => $purchaseReturn->items()->count(),
                    'total_amount' => (float) $purchaseReturn->total_amount,
                ])
                ->log('Confirmed Purchase Return');

            return back()->with('success', 'Purchase return confirmed and stock updated.');
        });
    }

    public function getReturnableItems(PurchaseOrder $order)
    {
        $order->load(['items.product', 'goodsReceipts.items', 'returns.items']);

        $items = $order->items->map(function ($item) use ($order) {
            // Total Received
            $receivedQty = $order->goodsReceipts->flatMap->items
                ->where('product_id', $item->product_id)
                ->sum('qty_received');
            
            // Total Returned
            $returnedQty = $order->returns->flatMap->items
                ->where('product_id', $item->product_id)
                ->sum('qty');

            $returnableQty = max(0, $receivedQty - $returnedQty);

            if ($returnableQty <= 0) {
                return null;
            }

            return [
                'po_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'sku' => $item->product->sku,
                'qty_ordered' => $item->qty,
                'qty_received' => $receivedQty,
                'qty_returned' => $returnedQty,
                'returnable_qty' => $returnableQty,
                'unit_price' => $item->unit_price,
            ];
        })->filter()->values();

        return response()->json([
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'items' => $items,
        ]);
    }
}
