<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesOrderExport;
use App\Imports\SalesOrderImport;
use App\Exports\Template\SalesOrderTemplateExport;

class SalesOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesOrder::with(['customer', 'warehouse', 'createdBy'])
            ->withCount('items')
            ->withSum('items as total_qty_ordered', 'qty')
            ->withSum('items as total_qty_delivered', 'qty_delivered')
            ->withSum('items as total_qty_invoiced', 'qty_invoiced')
            ->withSum('items as total_qty_returned', 'qty_returned')
            ->withSum(['invoices as total_amount_invoiced' => function($query) {
                $query->where('status', '!=', 'cancelled');
            }], 'total')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('so_number', 'like', "%{$search}%")
                      ->orWhere('customer_po_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->po_number, function ($q, $po_number) {
                $q->where('customer_po_number', 'like', "%{$po_number}%");
            })
            ->when($request->so_number, function ($q, $so_number) {
                $q->where('so_number', 'like', "%{$so_number}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->customer, function ($q, $customer) {
                $q->where('customer_id', $customer);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('sales_orders.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'sales_orders.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('sales_orders.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        // Calculate stats BEFORE paginate (clone must happen before query execution)
        $statsQuery = clone $query;
        $stats = [
            'total_qty' => (float) $statsQuery->sum(DB::raw('(select sum(qty) from sales_order_items where sales_order_id = sales_orders.id)')),
            'total_delivered' => (float) (clone $query)->sum(DB::raw('(select sum(qty_delivered) from sales_order_items where sales_order_id = sales_orders.id)')),
            'total_returned' => (float) (clone $query)->sum(DB::raw('(select sum(qty_returned) from sales_order_items where sales_order_id = sales_orders.id)')),
        ];

        $salesOrders = $query->paginate(20)
            ->withQueryString();
        $stats['total_balance'] = $stats['total_qty'] - ($stats['total_delivered'] - $stats['total_returned']);

        return Inertia::render('Sales/Orders/Index', [
            'salesOrders' => $salesOrders,
            'stats' => $stats,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'customer', 'po_number', 'so_number']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'waiting_po', 'label' => 'Waiting PO'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'processing', 'label' => 'Processing'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => null,
            'soNumber' => SalesOrder::generateSoNumber(),
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->select('id','sku','name','unit_id','cost_price','selling_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function createFromAi(Request $request): Response
    {
        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => null,
            'soNumber' => SalesOrder::generateSoNumber(),
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->select('id','sku','name','unit_id','cost_price','selling_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'aiData' => $request->input('data')
        ]);
    }

    public function checkPo(Request $request)
    {
        $poNumber = $request->input('po_number');
        if (!$poNumber) {
            return response()->json(['exists' => false]);
        }

        $existing = SalesOrder::where('customer_po_number', $poNumber)
            ->where('status', '!=', 'cancelled')
            ->first();

        return response()->json([
            'exists' => !!$existing,
            'so_number' => $existing?->so_number,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_number' => 'required|string|max:30|unique:sales_orders,so_number',
            'customer_po_number' => \App\Models\AppSetting::get('require_po_number', false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            $so = SalesOrder::create([
                'so_number' => $validated['so_number'],
                'customer_po_number' => $validated['customer_po_number'] ?? null,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'status' => 'draft',
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
                'shipping_name' => $validated['shipping_name'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $so->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);
            }

            // Recalculate totals after items are created
            $so->refresh();
            $so->calculateTotals();
        });

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order created successfully.');
    }

    public function show(SalesOrder $order): Response
    {
        // Optimized loading to include delivery items for calculation
        $order->load([
            'customer', 
            'warehouse', 
            'items.product' => function($q) { $q->withTrashed(); }, 
            'items.unit', 
            'items.deliveryOrderItems.deliveryOrder', // Eager load for reserved_qty optimization
            'createdBy', 
            'confirmedBy', 
            'deliveryOrders', 
            'invoices'
        ]);

        return Inertia::render('Sales/Orders/Show', [
            'salesOrder' => $order,
        ]);
    }

    public function edit(SalesOrder $order): Response
    {
        if (!$order->isEditable()) {
            return redirect()->route('sales.orders.show', $order)
                ->with('error', 'This sales order cannot be edited.');
        }

        $order->load(['items.product', 'items.unit']);

        return Inertia::render('Sales/Orders/Form', [
            'salesOrder' => $order,
            'soNumber' => $order->so_number,
            'customers' => Customer::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->select('id','sku','name','unit_id','cost_price','selling_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);

    }

    public function print(SalesOrder $order)
    {
        $order->load(['customer', 'items.product.partners', 'items.unit', 'createdBy', 'confirmedBy']);

        // Inject aliases
        foreach ($order->items as $item) {
            if ($item->product) {
                $alias = $item->product->getAliasFor($order->customer);
                $item->product_alias_name = $alias?->alias_name;
                $item->product_alias_sku = $alias?->alias_sku;
            }
        }

        return view('print.sales-order', ['salesOrder' => $order]);
    }

    public function update(Request $request, SalesOrder $order)
    {
        if (!$order->isEditable()) {
            return back()->with('error', 'This sales order cannot be edited.');
        }

        $validated = $request->validate([
            'customer_po_number' => \App\Models\AppSetting::get('require_po_number', false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:sales_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $order->update([
                'customer_po_number' => $validated['customer_po_number'] ?? null,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'notes' => $validated['notes'] ?? null,
                'shipping_name' => $validated['shipping_name'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
            ]);

            $existingIds = collect($validated['items'])->pluck('id')->filter()->all();
            
            // Delete removed items via Eloquent for logging
            $order->items()->whereNotIn('id', $existingIds)->get()->each(function($item) {
                if ($item->qty_delivered > 0) {
                    throw new \Exception("Cannot delete item [{$item->product->name}] because it has already been delivered.");
                }
                $item->delete();
            });

            foreach ($validated['items'] as $item) {
                if (isset($item['id'])) {
                    $order->items()->where('id', $item['id'])->update([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                } else {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                    ]);
                }
            }

            $order->refresh();
            $order->calculateTotals();
        });

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order updated successfully.');
    }

    public function confirm(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be confirmed.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Sales Order confirmed.');
    }

    public function bulkConfirm(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:sales_orders,id',
        ]);

        $orders = SalesOrder::whereIn('id', $request->ids)->get();
        $confirmed = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if ($order->status === 'draft') {
                $order->update([
                    'status' => 'confirmed',
                    'confirmed_by' => auth()->id(),
                    'confirmed_at' => now(),
                ]);
                $confirmed++;
            } else {
                $skipped++;
            }
        }

        $message = "{$confirmed} Sales Order berhasil di-confirm.";
        if ($skipped > 0) {
            $message .= " {$skipped} dilewati (bukan status draft).";
        }

        return back()->with($confirmed > 0 ? 'success' : 'error', $message);
    }

    public function cancel(SalesOrder $order)
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Sales Order cancelled.');
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $order->delete();

        return redirect()->route('sales.orders.index')
            ->with('success', 'Sales Order deleted successfully.');
    }

    public function updateItemQty(Request $request, \App\Models\SalesOrderItem $item)
    {
        $validated = $request->validate([
            'qty' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQty = $item->qty;
        $newQty = $validated['qty'];

        if ($oldQty == $newQty) {
            return back();
        }

        DB::transaction(function () use ($item, $newQty, $validated) {
            $item->update(['qty' => $newQty]);
            
            // Recalculate totals for the parent SO
            $item->salesOrder->calculateTotals();

            // Explicitly log the reason if provided
            if (!empty($validated['reason'])) {
                activity()
                    ->performedOn($item)
                    ->causedBy(auth()->user())
                    ->withProperties(['old_qty' => $oldQty, 'new_qty' => $newQty, 'reason' => $validated['reason']])
                    ->log("Adjusted quantity from {$oldQty} to {$newQty}. Reason: " . $validated['reason']);
            }
        });

        return back()->with('success', 'Quantity updated and logged successfully.');
    }

    public function updateItemPrice(Request $request, \App\Models\SalesOrderItem $item)
    {
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $so = $item->salesOrder;
        $hasBlockingInvoice = $so->invoices()
            ->where(function ($q) {
                $q->where('status', '!=', \App\Models\SalesInvoice::STATUS_DRAFT)
                    ->orWhere('paid_amount', '>', 0);
            })
            ->exists();

        if ($hasBlockingInvoice) {
            return back()->with('error', 'Tidak bisa merevisi harga karena SO ini sudah memiliki Invoice yang sudah terbit atau sudah ada pembayaran.');
        }

        $oldPrice = $item->unit_price;
        $newPrice = $validated['unit_price'];

        if ($oldPrice == $newPrice) {
            return back();
        }

        DB::transaction(function () use ($item, $newPrice, $validated, $oldPrice) {
            $item->update(['unit_price' => $newPrice]);
            
            // Recalculate totals for the parent SO
            $item->salesOrder->calculateTotals();

            $draftInvoices = $item->salesOrder
                ->invoices()
                ->where('status', \App\Models\SalesInvoice::STATUS_DRAFT)
                ->where('paid_amount', 0)
                ->get();

            foreach ($draftInvoices as $invoice) {
                foreach ($invoice->items()->where('sales_order_item_id', $item->id)->get() as $invoiceItem) {
                    $discountPercent = (float) ($invoiceItem->discount_percent ?? 0);
                    $discountAmount = ($invoiceItem->qty * $newPrice) * ($discountPercent / 100);
                    $subtotal = ($invoiceItem->qty * $newPrice) - $discountAmount;

                    $invoiceItem->update([
                        'unit_price' => $newPrice,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $subtotal,
                    ]);
                }

                $invoice->refresh();
                $invoice->save();
            }

            // Log the price change
            if (!empty($validated['reason'])) {
                activity()
                    ->performedOn($item)
                    ->causedBy(auth()->user())
                    ->withProperties(['old_price' => $oldPrice, 'new_price' => $newPrice, 'reason' => $validated['reason']])
                    ->log("Revised price from {$oldPrice} to {$newPrice}. Reason: " . $validated['reason']);
            }
        });

        return back()->with('success', 'Harga berhasil direvisi.');
    }


    public function createInvoice(SalesOrder $order)
    {
        \Log::info("Attempting to create consolidated invoice for SO: {$order->so_number}");

        // Determine items that can be invoiced (delivered but not yet invoiced)
        $itemsToInvoice = $order->items->filter(function($item) {
            $remaining = $item->qty_delivered - $item->qty_invoiced;
            return $remaining > 0;
        });

        if ($itemsToInvoice->isEmpty()) {
            \Log::warning("No items available to invoice for SO: {$order->so_number}");
            return back()->with('error', 'No delivered items are available for invoicing. They may have already been invoiced.');
        }

        // Prevent invoicing if Waiting PO (Direct DO case)
        if ($order->status === \App\Models\SalesOrder::STATUS_WAITING_PO) {
            return back()->with('error', "Gagal membuat Invoice. Order ini masih berstatus 'Waiting PO'. Harap revisi Sales Order terlebih dahulu untuk memasukkan Nomor PO resmi.");
        }

        \Log::info("Found " . $itemsToInvoice->count() . " items to invoice for SO: {$order->so_number}");

        try {
            return \DB::transaction(function () use ($order, $itemsToInvoice) {
                $lastDeliveryDate = \App\Models\DeliveryOrder::where('sales_order_id', $order->id)
                    ->whereIn('status', ['delivered', 'completed'])
                    ->max('delivery_date');
                $invoiceDate = $lastDeliveryDate ? Carbon::parse($lastDeliveryDate) : now();

                // Create the invoice
                $invoice = $order->invoices()->create([
                    'company_id' => $order->company_id ?? 1,
                    'customer_id' => $order->customer_id,
                    'invoice_number' => \App\Models\SalesInvoice::generateInvoiceNumber($order->customer),
                    'invoice_date' => $invoiceDate,
                    'due_date' => (clone $invoiceDate)->addDays($order->customer->payment_days ?? 30),
                    'status' => 'draft',
                    'tax_percent' => $order->tax_percent,
                    'notes' => 'Generated from Sales Order (Consolidated)',
                    'created_by' => auth()->id(),
                ]);

                foreach ($itemsToInvoice as $soItem) {
                    $uninvoicedQty = $soItem->qty_delivered - $soItem->qty_invoiced;
                    
                    // Find DO items for this SO item that haven't been fully invoiced
                    $doItems = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                        ->whereHas('deliveryOrder', function($q) {
                            $q->whereIn('status', ['delivered', 'completed']);
                        })
                        ->whereRaw('qty_delivered > qty_invoiced')
                        ->get();

                    foreach ($doItems as $doItem) {
                        if ($uninvoicedQty <= 0) break;

                        $take = min($uninvoicedQty, $doItem->qty_delivered - $doItem->qty_invoiced);
                        
                        $itemAmount = $take * $soItem->unit_price;
                        $discountAmt = $itemAmount * ($soItem->discount_percent / 100);
                        $lineTotal = $itemAmount - $discountAmt;

                        $invoice->items()->create([
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $soItem->product_id,
                            'description' => $soItem->product->name ?? $soItem->description,
                            'qty' => $take,
                            'unit_id' => $soItem->unit_id,
                            'unit_price' => $soItem->unit_price,
                            'discount_percent' => $soItem->discount_percent,
                            'discount_amount' => $discountAmt,
                            'subtotal' => $lineTotal,
                            'delivery_order_id' => $doItem->delivery_order_id,
                        ]);

                        // Update DO item tracking
                        $doItem->recalculateInvoiced();

                        // Update SO item tracking
                        $soItem->recalculateInvoiced();

                        $uninvoicedQty -= $take;
                    }
                }

                $invoice->calculateTotals();

                \Log::info("Invoice {$invoice->invoice_number} created successfully for SO: {$order->so_number}");

                return redirect()->route('sales.invoices.show', $invoice->id)
                    ->with('success', 'Consolidated invoice created successfully.');
            });
        } catch (\Exception $e) {
            \Log::error("Error creating consolidated invoice for SO {$order->so_number}: " . $e->getMessage());
            return back()->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new SalesOrderExport, 'sales_orders_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new SalesOrderImport($request->boolean('overwrite'));
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->importedCount} SO baru dibuat";
        if ($import->updatedCount > 0) {
            $message .= ", {$import->updatedCount} SO diupdate";
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
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\SalesOrderDataExport, 'sales_orders_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new SalesOrderTemplateExport, 'sales_orders_template.xlsx');
    }
}
