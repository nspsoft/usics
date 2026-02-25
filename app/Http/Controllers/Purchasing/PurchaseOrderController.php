<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): Response
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'createdBy'])
            ->withCount('items')
            ->withSum('items as total_qty', 'qty')
            ->withSum('items as total_received', 'qty_received')
            ->withSum('items as total_returned', 'qty_returned')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                if ($status === 'outstanding') {
                    $q->whereIn('status', ['ordered', 'partial']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->supplier, function ($q, $supplier) {
                $q->where('supplier_id', $supplier);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction)
                  ->select('purchase_orders.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'purchase_orders.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('purchase_orders.*');
        } elseif (in_array($sort, ['total_qty', 'total_received', 'total_returned', 'total'])) {
            $query->orderBy($sort, $direction);
        } elseif (in_array($sort, ['po_number', 'order_date', 'expected_date', 'status', 'created_at'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', $direction);
        }

        $purchaseOrders = $query->paginate(20)->withQueryString();

        // Calculate stats from a fresh base query (Bug 7 fix: avoid cloning after join)
        $statsBaseQuery = PurchaseOrder::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                if ($status === 'outstanding') {
                    $q->whereIn('status', ['ordered', 'partial']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->supplier, function ($q, $supplier) {
                $q->where('supplier_id', $supplier);
            });
        
        $stats = [
            'total_qty' => $statsBaseQuery->sum(DB::raw('(select sum(qty) from purchase_order_items where purchase_order_id = purchase_orders.id)')),
            'total_received' => $statsBaseQuery->sum(DB::raw('(select sum(qty_received) from purchase_order_items where purchase_order_id = purchase_orders.id)')),
            'total_returned' => $statsBaseQuery->sum(DB::raw('(select sum(qty_returned) from purchase_order_items where purchase_order_id = purchase_orders.id)')),
        ];
        $stats['total_balance'] = $stats['total_qty'] - ($stats['total_received'] - $stats['total_returned']);

        return Inertia::render('Purchasing/Orders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'stats' => $stats,
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'supplier', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'outstanding', 'label' => 'Outstanding (Open)'],
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'submitted', 'label' => 'Submitted'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'ordered', 'label' => 'Ordered'],
                ['value' => 'partial', 'label' => 'Partial Received'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create(Request $request): Response
    {
        $purchaseOrder = null;

        // Convert from PR
        if ($request->has('from_pr')) {
            $pr = PurchaseRequest::with('items')->find($request->from_pr);
            if ($pr && $pr->status === 'approved') {
                $items = $pr->items->map(function ($item) {
                    $product = Product::find($item->product_id);
                    return [
                        'product_id' => $item->product_id,
                        'qty' => $item->qty,
                        'unit_price' => $product ? $product->cost_price : 0,
                        'discount_percent' => 0,
                        'unit_id' => $product ? $product->unit_id : null,
                        'description' => $item->description,
                    ];
                });

                $purchaseOrder = new PurchaseOrder([
                    'order_date' => date('Y-m-d'),
                    'notes' => "Converted from PR {$pr->pr_number}. " . $pr->notes,
                ]);
                $purchaseOrder->setRelation('items', $items);
            }
        }

        return Inertia::render('Purchasing/Orders/Form', [
            'purchaseOrder' => $purchaseOrder,
            'poNumber' => null,
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->with('unit')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $poNumber = app(DocumentNumberService::class)->generate(
                'purchase_order',
                ['SUPP_CODE' => $supplier->code ?? ''],
                $validated['order_date']
            );

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'status' => 'draft',
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'notes' => $item['notes'] ?? null,
                    'qty' => $item['qty'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);
            }
        });

        return redirect()->route('purchasing.orders.index')
            ->with('success', 'Purchase Order created successfully.');
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $order): Response
    {
        $order->load(['supplier', 'warehouse', 'items.product', 'items.unit', 'createdBy', 'approvedBy', 'goodsReceipts']);

        return Inertia::render('Purchasing/Orders/Show', [
            'purchaseOrder' => $order,
        ]);
    }

    /**
     * Print the specified purchase order.
     */
    public function print(PurchaseOrder $order)
    {
        $order->load(['supplier', 'warehouse', 'items.product.partners', 'items.unit', 'createdBy', 'approvedBy']);

        // Inject aliases
        foreach ($order->items as $item) {
            if ($item->product) {
                $alias = $item->product->getAliasFor($order->supplier);
                $item->product_alias_name = $alias?->alias_name;
                $item->product_alias_sku = $alias?->alias_sku;
            }
        }

        return view('print.purchase-order', ['purchaseOrder' => $order]);
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $order)
    {
        if (!$order->isEditable()) {
            return redirect()->route('purchasing.orders.show', $order)
                ->with('error', 'This purchase order cannot be edited.');
        }

        $order->load(['items.product', 'items.unit']);

        return Inertia::render('Purchasing/Orders/Form', [
            'purchaseOrder' => $order,
            'poNumber' => $order->po_number,
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->with('unit')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified purchase order.
     */
    public function update(Request $request, PurchaseOrder $order)
    {
        if (!$order->isEditable()) {
            return back()->with('error', 'This purchase order cannot be edited.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Get existing item IDs
            $existingIds = collect($validated['items'])->pluck('id')->filter()->all();
            
            // Delete items that are not in the request
            $order->items()->whereNotIn('id', $existingIds)->get()->each->delete();

            // Update or create items
            foreach ($validated['items'] as $item) {
                if (isset($item['id'])) {
                    $existingItem = $order->items()->find($item['id']);
                    if ($existingItem && $item['qty'] < $existingItem->qty_received) {
                        throw new \Exception("Quantity for product {$existingItem->product->name} cannot be less than already received quantity ({$existingItem->qty_received}).");
                    }

                    $existingItem->update([
                        'product_id' => $item['product_id'],
                        'notes' => $item['notes'] ?? null,
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                } else {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'notes' => $item['notes'] ?? null,
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                }
            }

            $order->refresh()->load('items');
            $order->calculateTotals();
        });

        return redirect()->route('purchasing.orders.index')
            ->with('success', 'Purchase Order updated successfully.');
    }

    /**
     * Submit purchase order for approval.
     */
    public function submit(PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be submitted.');
        }

        $order->update(['status' => 'submitted']);

        return back()->with('success', 'Purchase Order submitted for approval.');
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $order)
    {
        if ($order->status !== 'submitted') {
            return back()->with('error', 'Only submitted orders can be approved.');
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Purchase Order approved.');
    }

    /**
     * Mark as ordered (sent to supplier).
     */
    public function markOrdered(PurchaseOrder $order)
    {
        if ($order->status !== 'approved') {
            return back()->with('error', 'Only approved orders can be marked as ordered.');
        }

        $order->update(['status' => 'ordered']);

        return back()->with('success', 'Purchase Order marked as ordered.');
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $order)
    {
        if (in_array($order->status, ['received', 'cancelled'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        // Bug 3 fix: Check if any completed Goods Receipts exist
        if ($order->goodsReceipts()->where('status', 'completed')->exists()) {
            return back()->with('error', 'Cannot cancel: this order has completed goods receipts. Please process a Purchase Return instead.');
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase Order cancelled.');
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $order->delete();

        return redirect()->route('purchasing.orders.index')
            ->with('success', 'Purchase Order deleted successfully.');
    }

    public function updateItemQty(Request $request, PurchaseOrderItem $item)
    {
        $validated = $request->validate([
            'qty' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQty = $item->qty;
        $newQty = $validated['qty'];

        $alreadyReceived = ($item->qty_received ?? 0) - ($item->qty_returned ?? 0);
        if ($newQty < max(0, $alreadyReceived)) {
            return back()->with('error', "Quantity cannot be less than already received quantity (" . max(0, $alreadyReceived) . ").");
        }

        if ($oldQty == $newQty) {
            return back();
        }

        DB::transaction(function () use ($item, $newQty, $validated, $oldQty) {
            $item->update(['qty' => $newQty]);
            
            // Recalculate totals for the parent PO
            $item->purchaseOrder->calculateTotals();

            // Explicitly log the reason if provided
            if (!empty($validated['reason'])) {
                activity()
                    ->performedOn($item)
                    ->causedBy(auth()->user())
                    ->withProperties(['old_qty' => $oldQty, 'new_qty' => $newQty, 'reason' => $validated['reason']])
                    ->log("Adjusted PO item quantity from {$oldQty} to {$newQty}. Reason: " . $validated['reason']);
            }
        });

        return back()->with('success', 'Quantity updated and logged successfully.');
    }

    public function publicValidate($id)
    {
        $order = PurchaseOrder::with(['supplier', 'items.product.unit', 'createdBy'])
            ->findOrFail($id);

        return view('print.public-purchase-order-validation', [
            'purchaseOrder' => $order
        ]);
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PurchaseOrdersExport, 'purchase_orders.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\PurchaseOrdersImport, $request->file('file'));
            return back()->with('success', 'Purchase Orders imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function template()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Template\PurchaseOrderTemplateExport, 'purchase_order_template.xlsx');
    }
}
