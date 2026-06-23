<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
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
            ->withCount(['subcontractOrder as subcontract_order_count'])
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
            })
            ->when($request->created_by, function ($q, $createdBy) {
                $q->where('created_by', $createdBy);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction)
                  ->select('purchase_orders.*');
        } elseif ($sort === 'created_by_name') {
            $query->leftJoin('users', 'purchase_orders.created_by', '=', 'users.id')
                  ->orderBy('users.name', $direction)
                  ->select('purchase_orders.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'purchase_orders.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('purchase_orders.*');
        } elseif (in_array($sort, ['total_qty', 'total_received', 'total_returned', 'total'])) {
            $query->orderBy($sort, $direction);
        } elseif (in_array($sort, ['po_number', 'order_date', 'expected_date', 'status', 'created_at', 'items_count'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', $direction);
        }

        $purchaseOrders = $query->paginate(20)->withQueryString();
        $purchaseOrders->getCollection()->transform(function ($po) {
            $po->is_subcontract = ((int) ($po->subcontract_order_count ?? 0)) > 0;
            return $po;
        });

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
            })
            ->when($request->created_by, function ($q, $createdBy) {
                $q->where('created_by', $createdBy);
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
            'users' => User::query()
                ->whereIn('id', PurchaseOrder::query()->whereNotNull('created_by')->distinct()->pluck('created_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'supplier', 'created_by', 'sort', 'direction']),
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
            'products' => Product::active()->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function generateNextNumber(Request $request)
    {
        $supplierId = $request->input('supplier_id');
        $date = $request->input('order_date');
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        return response()->json([
            'number' => PurchaseOrder::previewPoNumber($supplier, $date)
        ]);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_number' => 'nullable|string|max:100|unique:purchase_orders,po_number',
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

            // Use user-provided PO number if available (Admin only), otherwise auto-generate
            $isAdmin = auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('IT Administrator');
            if ($isAdmin && !empty($validated['po_number'])) {
                $poNumber = $validated['po_number'];
                // Sync the counter so next auto-generation continues from here
                app(DocumentNumberService::class)->sync('purchase_order', $poNumber);
            } else {
                $poNumber = app(DocumentNumberService::class)->generate(
                    'purchase_order',
                    ['SUPP_CODE' => $supplier->code ?? ''],
                    $validated['order_date']
                );
            }

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
        $order->load(['supplier', 'warehouse', 'items.product', 'items.unit', 'createdBy', 'approvedBy', 'goodsReceipts', 'subcontractOrder']);
        $order->is_subcontract = $order->subcontractOrder !== null;

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
     * Duplicate the specified purchase order into a new draft.
     */
    public function duplicate(PurchaseOrder $order)
    {
        $newPoId = DB::transaction(function () use ($order) {
            $order->load('items');
            
            // Generate new PO Number
            $poNumber = app(DocumentNumberService::class)->generate(
                'purchase_order',
                ['SUPP_CODE' => $order->supplier->code ?? ''],
                date('Y-m-d')
            );
            
            // Replicate header
            $newPo = $order->replicate()->fill([
                'po_number' => $poNumber,
                'order_date' => date('Y-m-d'),
                'expected_date' => null,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'approved_by' => null,
                'approved_at' => null,
            ]);
            $newPo->save();
            
            // Replicate items
            foreach ($order->items as $item) {
                $newItem = $item->replicate()->fill([
                    'purchase_order_id' => $newPo->id,
                    'qty_received' => 0,
                    'qty_returned' => 0,
                ]);
                $newItem->save();
            }
            
            return $newPo->id;
        });

        return redirect()->route('purchasing.orders.edit', $newPoId)
            ->with('success', 'Purchase Order duplicated successfully. You can now edit the new draft.');
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
            'products' => Product::active()->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
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
            'po_number' => 'required|string|max:100|unique:purchase_orders,po_number,' . $order->id,
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

        $isAdmin = auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('IT Administrator');
        if (!$isAdmin && $order->po_number !== $validated['po_number']) {
            return back()->withErrors(['po_number' => 'Hanya Admin yang dapat mengubah PO Number secara manual.']);
        }

        DB::transaction(function () use ($validated, $order) {
        $updateData = [
            'po_number' => $validated['po_number'],
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? 0,
            'tax_percent' => $validated['tax_percent'] ?? 11,
            'notes' => $validated['notes'] ?? null,
        ];

        // Sync the counter if changed manually
        app(DocumentNumberService::class)->sync('purchase_order', $validated['po_number']);

        // Handle revision if status is finalized (approved/ordered/partial)
        if (in_array($order->status, ['approved', 'ordered', 'partial'])) {
             $baseNumber = $order->po_number;
             // Remove existing revision suffix if any to get base
             if (strpos($baseNumber, '-R') !== false) {
                 // Check if it's genuinely a revision suffix like -R1, -R2
                 $parts = explode('-R', $baseNumber);
                 if (is_numeric(end($parts))) {
                     array_pop($parts);
                     $baseNumber = implode('-R', $parts);
                 }
             }
             
             // Increment revision
             $newRevision = ($order->revision ?? 0) + 1;
             
             $updateData['status'] = 'draft';
             $updateData['revision'] = $newRevision;
             $updateData['po_number'] = $baseNumber . '-R' . $newRevision;
             $updateData['approved_by'] = null;
             $updateData['approved_at'] = null;
        }

        $order->update($updateData);
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

    public function bulkMarkOrdered(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique($validated['ids']));

        $orders = PurchaseOrder::whereIn('id', $ids)->get(['id', 'status']);
        $eligibleIds = $orders->where('status', 'approved')->pluck('id')->values();
        $skipped = $orders->count() - $eligibleIds->count();

        if ($eligibleIds->isEmpty()) {
            return back()->with('error', 'No approved orders selected. Only approved orders can be marked as ordered.');
        }

        DB::transaction(function () use ($eligibleIds) {
            $orders = PurchaseOrder::whereIn('id', $eligibleIds)->get();
            foreach ($orders as $order) {
                $order->update(['status' => 'ordered']);
            }
        });

        $message = "Marked {$eligibleIds->count()} purchase orders as ordered.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} because status was not approved.";
        }

        return back()->with('success', $message);
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
            $item->purchaseOrder->updateStatusBasedOnItems();

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
            $import = new \App\Imports\PurchaseOrdersImport($request->boolean('overwrite'));
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $message = "Import selesai: {$import->importedCount} PO dibuat";
            if ($import->updatedCount > 0) {
                $message .= ", {$import->updatedCount} PO diupdate";
            }
            $message .= ".";
            if ($import->skippedCount > 0) {
                $message .= " {$import->skippedCount} baris dilewati.";
            }
            if (!empty($import->errors)) {
                $message .= ' Errors: ' . implode('; ', array_slice($import->errors, 0, 5));
            }

            $hasSuccess = ($import->importedCount > 0 || $import->updatedCount > 0);
            return back()->with($hasSuccess ? 'success' : 'error', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            $includeAll = $request->boolean('all');
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseOrderDataExport($includeAll),
                'purchase_orders_data_' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Template\PurchaseOrderTemplateExport, 'purchase_order_template.xlsx');
    }
}
