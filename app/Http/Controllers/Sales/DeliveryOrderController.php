<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryOrderExport;
use App\Imports\DeliveryOrderImport;
use App\Exports\Template\DeliveryOrderTemplateExport;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeliveryOrder::with(['salesOrder' => function($q) {
                $q->select('id', 'so_number', 'customer_po_number', 'customer_id'); // Select specific fields including PO
            }, 'salesOrder.customer', 'warehouse', 'items'])
            ->withCount('items')
            ->withSum('items as total_qty', 'qty_delivered') // Add total_qty sum
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                      ->orWhere('shipping_name', 'like', "%{$search}%")
                      ->orWhereHas('salesOrder', function ($sq) use ($search) {
                          $sq->where('so_number', 'like', "%{$search}%")
                             ->orWhere('customer_po_number', 'like', "%{$search}%") // Search by PO too
                             ->orWhereHas('customer', function ($cq) use ($search) {
                                 $cq->where('name', 'like', "%{$search}%");
                             });
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->customer, function ($q, $customer) {
                $q->where('customer_id', $customer);
            })
            ->when($request->delivery_date_from, function ($q, $date) {
                $q->whereDate('delivery_date', '>=', $date);
            })
            ->when($request->delivery_date_to, function ($q, $date) {
                $q->whereDate('delivery_date', '<=', $date);
            })
            ->when($request->invoice_status, function ($q, $status) {
                $q->invoiceStatus($status);
            });

        $sort = $request->input('sort', 'delivery_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->leftJoin('customers', 'delivery_orders.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('delivery_orders.*');
        } elseif ($sort === 'so_number') {
            $query->leftJoin('sales_orders', 'delivery_orders.sales_order_id', '=', 'sales_orders.id')
                  ->orderBy('sales_orders.so_number', $direction)
                  ->select('delivery_orders.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $deliveryOrders = $query
            ->paginate(20)
            ->withQueryString();
            // Removed makeHidden('items') to allow frontend to access items if needed for detailed view preview, 
            // though we have aggregations now.

        // SOs that can be delivered (Confirmed/Processing and have undelivered items)
        $pendingSalesOrders = \App\Models\SalesOrder::whereIn('status', ['confirmed', 'processing'])
            ->whereHas('items', function($q) {
                $q->whereRaw('qty > qty_delivered');
            })
            ->with('customer')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Sales/Deliveries/Index', [
            'deliveryOrders' => $deliveryOrders,
            'pendingSalesOrders' => $pendingSalesOrders,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'invoice_status', 'customer', 'delivery_date_from', 'delivery_date_to']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'picking', 'label' => 'Picking'],
                ['value' => 'packed', 'label' => 'Packed'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered (Driver)'],
                ['value' => 'completed', 'label' => 'Completed (Admin)'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function bulkCreateInvoice(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:delivery_orders,id',
        ]);

        $deliveryOrders = DeliveryOrder::whereIn('id', $request->ids)
            ->with(['items.salesOrderItem.product', 'items.salesOrderItem', 'salesOrder'])
            ->get();

        if ($deliveryOrders->isEmpty()) {
            return back()->with('error', 'No delivery orders selected.');
        }

        // Validate distinct customers
        $customerIds = $deliveryOrders->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            return back()->with('error', 'Selected delivery orders must belong to the same customer.');
        }

        // Validate status
        $invalidStatus = $deliveryOrders->contains(fn($do) => !in_array($do->status, ['delivered', 'completed']));
        if ($invalidStatus) {
            return back()->with('error', 'All selected delivery orders must be Delivered or Completed.');
        }
        
        // Validate not already fully invoiced
        $alreadyInvoiced = $deliveryOrders->contains(fn($do) => $do->invoice_status === 'invoiced');
        if ($alreadyInvoiced) {
            return back()->with('error', 'Some selected delivery orders are already fully invoiced.');
        }

        try {
            DB::beginTransaction();

            $firstDO = $deliveryOrders->first();
            $customer = $firstDO->customer ?? $firstDO->salesOrder->customer;
            
            // Create Invoice
            $invoice = new \App\Models\SalesInvoice();
            $invoice->company_id = $firstDO->company_id ?? 1;
            $invoice->customer_id = $customer->id;
            $invoice->sales_order_id = $firstDO->sales_order_id; // Link to the first SO as primary reference, or leave null/manage many-to-many if supported. 
            // Note: Current schema assumes one SO per Invoice strictly? 
            // Schema check: invoices table usually has sales_order_id.
            // If we consolidate multiple DOs from DIFFERENT SOs, this might be tricky if schema enforces 1 SO.
            // Let's assume for now they might be from same SO because users usually invoice per SO or per Project. 
            // BUT the user request implies simply selecting DOs. If they are from different SOs but same Customer, we can support it 
            // IF the invoice->sales_order_id is nullable or we treat it loosely. 
            // For safety, let's link to the first SO, or check if schema allows nullable. 
            // Actually, previously I used $so->invoices()->create(). 
            // If DOs are from different SOs, we just pick the first one as "Master" SO or leave it if schema allows.
            // Let's assume same Customer is the constraint.
            
            $invoice->invoice_number = \App\Models\SalesInvoice::generateInvoiceNumber($customer);
            $invoice->invoice_date = now();
            $invoice->due_date = now()->addDays(30); // Default term
            $invoice->status = 'draft';
            $invoice->tax_percent = $firstDO->salesOrder->tax_percent ?? 11;
            $invoice->notes = 'Consolidated Invoice from DOs: ' . $deliveryOrders->pluck('do_number')->implode(', ');
            $invoice->created_by = auth()->id();

            // Validation: Check for Waiting PO status BEFORE saving
            foreach ($deliveryOrders as $do) {
                if ($do->salesOrder && $do->salesOrder->status === SalesOrder::STATUS_WAITING_PO) {
                    return back()->with('error', "Gagal membuat Invoice. DO {$do->do_number} masih berstatus 'Waiting PO'. Harap revisi SO terlebih dahulu untuk memasukkan Nomor PO resmi.");
                }
            }

            $invoice->save();

            // Process Items
            foreach ($deliveryOrders as $do) {
                foreach ($do->items as $doItem) {
                    $qtyToInvoice = $doItem->qty_delivered - $doItem->qty_invoiced;
                    
                    if ($qtyToInvoice <= 0) continue;

                    $soItem = $doItem->salesOrderItem;
                    if (!$soItem) continue;

                    // Calculate price
                    $price = $soItem->unit_price;
                    $discountPct = $soItem->discount_percent;
                    $discountAmt = ($qtyToInvoice * $price) * ($discountPct / 100);
                    $subtotal = ($qtyToInvoice * $price) - $discountAmt;

                    $invoice->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $doItem->product_id,
                        'description' => $doItem->product->name ?? $soItem->product_name, // Ensure description is filled
                        'qty' => $qtyToInvoice,
                        'unit_id' => $doItem->unit_id,
                        'unit_price' => $price,
                        'discount_percent' => $discountPct,
                        'discount_amount' => $discountAmt,
                        'subtotal' => $subtotal,
                        'delivery_order_id' => $do->id, // Important for tracking
                    ]);

                    // Update tracking ATOMICALLY
                    $doItem->increment('qty_invoiced', $qtyToInvoice);
                    
                    $soItem->increment('qty_invoiced', $qtyToInvoice);
                }
                
                // Refresh the invoice status for the DO
                $do->refreshInvoiceStatus();
            }

            $invoice->calculateTotals();
            
            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice->id)
                ->with('success', 'Consolidated Invoice created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function create(Request $request): Response
    {
        $salesOrder = null;
        if ($request->sales_order_id) {
            $salesOrder = SalesOrder::with(['customer', 'warehouse'])->find($request->sales_order_id);
        }

        $pendingSalesOrders = SalesOrder::whereIn('status', ['confirmed', 'processing', 'partial'])
            ->whereHas('items', function($q) {
                // Optimization: Filter at DB level.
                // We use the cached 'qty_delivered' column which we have now secured with robust logic.
                // qty > qty_delivered means there is remaining quantity.
                $q->whereRaw('qty > qty_delivered');
            })
            ->with(['customer'])
            ->orderByDesc('id')
            ->limit(100) // Use limit instead of take for DB query
            ->get();

        return Inertia::render('Sales/Deliveries/Create', [
            'salesOrder' => $salesOrder,
            'salesOrders' => $pendingSalesOrders,
            'vehicles' => Vehicle::where('is_active', true)->whereIn('usage_type', ['logistics', 'both'])->orderBy('license_plate')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'customers' => \App\Models\Customer::orderBy('name')->get(['id', 'name', 'code']),
            'products' => \App\Models\Product::with('unit:id,name,symbol')->orderBy('name')->get(['id', 'name', 'sku', 'unit_id'])->each->setAppends([]),
        ]);
    }

    public function getSoItems(SalesOrder $sales_order)
    {
        $sales_order->load(['items.product', 'items.unit', 'items.deliveryOrderItems.deliveryOrder']);

        $items = $sales_order->items->map(function ($item) {
            $remaining = $item->remaining_qty;
            
            if ($remaining <= 0) return null;

            return [
                'sales_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'N/A',
                'sku' => $item->product->sku ?? 'N/A',
                'qty_ordered' => (float) $item->qty,
                'remaining' => (float) $remaining,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name ?? 'N/A',
            ];
        })->filter()->values();

        return response()->json([
            'customer_id' => $sales_order->customer_id,
            'warehouse_id' => $sales_order->warehouse_id,
            'shipping_address' => $sales_order->shipping_address,
            'items' => $items,
        ]);
    }

    public function getAddItemOptions(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return response()->json(['message' => 'Hanya Delivery Order berstatus Draft yang bisa ditambah item.'], 422);
        }

        $deliveryOrder->load(['salesOrder.items.product', 'salesOrder.items.unit', 'items']);

        $salesOrder = $deliveryOrder->salesOrder;
        $isDirect = !$salesOrder || $salesOrder->status === SalesOrder::STATUS_WAITING_PO;

        if (!$isDirect && $salesOrder) {
            $existingSoItemIds = $deliveryOrder->items->pluck('sales_order_item_id')->all();

            $items = $salesOrder->items->map(function ($item) use ($existingSoItemIds) {
                if (in_array($item->id, $existingSoItemIds)) return null;

                $remaining = $item->remaining_qty;
                if ($remaining <= 0) return null;

                return [
                    'sales_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name ?? 'N/A',
                    'sku' => $item->product->sku ?? 'N/A',
                    'qty_ordered' => (float) $item->qty,
                    'remaining' => (float) $remaining,
                    'unit_id' => $item->unit_id,
                    'unit_code' => $item->unit->code ?? null,
                ];
            })->filter()->values();

            return response()->json([
                'mode' => 'so',
                'items' => $items,
            ]);
        }

        $existingProductIds = $deliveryOrder->items->pluck('product_id')->all();

        $productsQuery = Product::active()
            ->where('is_sold', true)
            ->with('unit')
            ->when($request->query('q'), function ($q, $term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->when(!empty($existingProductIds), fn ($q) => $q->whereNotIn('id', $existingProductIds))
            ->orderBy('name')
            ->limit(50);

        $products = $productsQuery->get(['id', 'name', 'sku', 'unit_id'])->map(function ($p) {
            return [
                'product_id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit_id' => $p->unit_id,
                'unit_code' => $p->unit?->code,
            ];
        })->values();

        return response()->json([
            'mode' => 'direct',
            'items' => $products,
        ]);
    }

    public function storeItem(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Hanya Delivery Order berstatus Draft yang bisa ditambah item.');
        }

        $deliveryOrder->load(['salesOrder', 'items']);
        $salesOrder = $deliveryOrder->salesOrder;
        $isDirect = !$salesOrder || $salesOrder->status === SalesOrder::STATUS_WAITING_PO;

        if (!$isDirect) {
            $validated = $request->validate([
                'sales_order_item_id' => 'required|exists:sales_order_items,id',
                'qty_delivered' => 'required|numeric|gt:0',
                'notes' => 'nullable|string|max:255',
            ]);

            $soItem = SalesOrderItem::with(['product', 'unit'])->findOrFail($validated['sales_order_item_id']);
            if ((int) $soItem->sales_order_id !== (int) $deliveryOrder->sales_order_id) {
                return back()->with('error', 'Item Sales Order tidak sesuai dengan Delivery Order ini.');
            }

            $exists = \App\Models\DeliveryOrderItem::where('delivery_order_id', $deliveryOrder->id)
                ->where('sales_order_item_id', $soItem->id)
                ->exists();
            if ($exists) {
                return back()->with('error', 'Item ini sudah ada di Delivery Order.');
            }

            $realDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                ->whereHas('deliveryOrder', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('qty_delivered');
            $qtyReturned = $soItem->returnItems()->sum('qty');
            $netDelivered = $realDelivered - $qtyReturned;
            $allowable = $soItem->qty - $netDelivered;

            if ((float) $validated['qty_delivered'] > ((float) $allowable + 0.0001)) {
                return back()->with('error', "Kuantitas melebihi sisa pesanan. Sisa maksimum: {$allowable}.");
            }

            \App\Models\DeliveryOrderItem::create([
                'delivery_order_id' => $deliveryOrder->id,
                'sales_order_item_id' => $soItem->id,
                'product_id' => $soItem->product_id,
                'qty_ordered' => (float) $soItem->qty,
                'qty_delivered' => (float) $validated['qty_delivered'],
                'unit_id' => $soItem->unit_id,
                'notes' => $validated['notes'] ?? null,
            ]);

            return back()->with('success', 'Item berhasil ditambahkan ke Delivery Order.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_delivered' => 'required|numeric|gt:0',
            'notes' => 'nullable|string|max:255',
        ]);

        if (!$salesOrder) {
            return back()->with('error', 'Delivery Order ini tidak memiliki Sales Order.');
        }

        if ($salesOrder->status !== SalesOrder::STATUS_WAITING_PO) {
            return back()->with('error', 'Penambahan produk bebas hanya diizinkan untuk Direct Delivery Order.');
        }

        $exists = \App\Models\DeliveryOrderItem::where('delivery_order_id', $deliveryOrder->id)
            ->where('product_id', $validated['product_id'])
            ->exists();
        if ($exists) {
            return back()->with('error', 'Produk ini sudah ada di Delivery Order.');
        }

        $product = Product::with('unit')->findOrFail($validated['product_id']);
        if (!$product->is_sold || !$product->is_active) {
            return back()->with('error', 'Produk tidak tersedia untuk dijual.');
        }
        if (!$product->unit_id) {
            return back()->with('error', 'Unit produk belum diatur.');
        }

        $soItem = $salesOrder->items()->create([
            'product_id' => $product->id,
            'qty' => (float) $validated['qty_delivered'],
            'unit_id' => $product->unit_id,
            'unit_price' => 0,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 0,
        ]);

        \App\Models\DeliveryOrderItem::create([
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $product->id,
            'qty_ordered' => (float) $soItem->qty,
            'qty_delivered' => (float) $validated['qty_delivered'],
            'unit_id' => $product->unit_id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Item berhasil ditambahkan ke Delivery Order.');
    }

    public function store(Request $request)
    {
        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_date' => 'required|date',
            'vehicle_id' => 'nullable',
            'vehicle_number' => 'required_if:vehicle_id,manual',
            'driver_name' => 'required',
            'items' => 'required|array|min:1',
            'items.*.qty_delivered' => 'required|numeric|gt:0',
        ];

        // Conditional validation
        if ($request->sales_order_id) {
            $rules['sales_order_id'] = 'exists:sales_orders,id';
            $rules['items.*.sales_order_item_id'] = 'required|exists:sales_order_items,id';
        } else {
            $rules['customer_id'] = 'required|exists:customers,id';
            $rules['items.*.product_id'] = 'required|exists:products,id';
            $rules['items.*.unit_id'] = 'required|exists:units,id';
        }

        $request->validate($rules);

        try {
            return DB::transaction(function () use ($request) {
                // Determine Sales Order
                if ($request->sales_order_id) {
                    $order = SalesOrder::findOrFail($request->sales_order_id);
                } else {
                    // Auto-create Sales Order for Direct DO (Waiting PO)
                    $order = SalesOrder::create([
                        'so_number' => SalesOrder::generateSoNumber(),
                        'customer_id' => $request->customer_id,
                        'warehouse_id' => $request->warehouse_id,
                        'order_date' => now(),
                        'status' => SalesOrder::STATUS_WAITING_PO,
                        'notes' => 'Auto-created from Direct Delivery Order',
                        'shipping_address' => $request->shipping_address,
                        'created_by' => auth()->id(),
                        'subtotal' => 0,
                        'total' => 0,
                    ]);
                }

                // Generate Custom DO Number: 020/DO/JRI-KBI/II/26
                $customer = \App\Models\Customer::find($order->customer_id);
                $custCode = $customer ? ($customer->code ?? 'GEN') : 'GEN';
                $monthRoman = $this->getRomanMonth(date('n'));

                try {
                    $number = app(\App\Services\DocumentNumberService::class)->generate('delivery_order', [
                        'CUST_CODE' => $custCode,
                        'ROMAN_MONTH' => $monthRoman
                    ]);
                } catch (\Exception $e) {
                    // Fallback to manual generation if service fails
                    $yearShort = date('y');
                    $formatSuffix = "/DO/JRI-{$custCode}/{$monthRoman}/{$yearShort}";
                    
                    if (DB::connection()->getDriverName() === 'mysql') {
                        $lastDO = DeliveryOrder::where('do_number', 'REGEXP', '^[0-9]+/DO/JRI-')
                            ->orderByRaw('CAST(SUBSTRING_INDEX(do_number, "/", 1) AS UNSIGNED) DESC')
                            ->first();
                    } else {
                        $lastDO = DeliveryOrder::where('do_number', 'like', '%/DO/JRI-%')
                            ->orderByDesc('id')
                            ->first();
                    }

                    if ($lastDO) {
                        $parts = explode('/', $lastDO->do_number, 2);
                        $lastRun = ctype_digit($parts[0]) ? (int) $parts[0] : 0;
                        $nextRun = $lastRun + 1;
                    } else {
                        $nextRun = 1; 
                    }
                    
                    $nextRunPadded = str_pad($nextRun, 3, '0', STR_PAD_LEFT);
                    $number = "{$nextRunPadded}{$formatSuffix}";
                }

                $doData = [
                    'do_number' => $number,
                    'sales_order_id' => $order->id, // Always link to an SO (existing or new)
                    'customer_id' => $order->customer_id,
                    'warehouse_id' => $request->warehouse_id,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_id' => $request->vehicle_id === 'manual' ? null : $request->vehicle_id,
                    'vehicle_number' => $request->vehicle_number,
                    'driver_name' => $request->driver_name,
                    'shipping_address' => $request->shipping_address ?? $order->shipping_address,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ];

                $do = DeliveryOrder::create($doData);

                foreach ($request->items as $itemData) {
                    if ($request->sales_order_id) {
                        // Logic with Existing SO
                        $soItem = SalesOrderItem::findOrFail($itemData['sales_order_item_id']);
                        
                        // Cross-check allowable again in backend using REAL-TIME AGGREGATION
                        // 1. Calculate Real Delivered Total (Other DOs)
                        $realDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                            ->whereHas('deliveryOrder', function($q) {
                                $q->where('status', '!=', 'cancelled');
                            })
                            ->sum('qty_delivered');
                        
                        // 2. Calculate Returned Qty
                        $qtyReturned = $soItem->returnItems()->sum('qty');
                        
                        // 3. Net Delivered (Real)
                        $netDelivered = $realDelivered - $qtyReturned;
                        
                        // 4. Calculate Real Remaining
                        $allowable = $soItem->qty - $netDelivered;
                        
                        if ($itemData['qty_delivered'] > ($allowable + 0.0001)) { // Float tolerance
                            throw new \Exception("Kuantitas untuk [{$soItem->product->name}] melebihi sisa pesanan (Maks: {$allowable}).");
                        }
                    } else {
                        // Logic for Direct DO: Create SO Item first
                        $soItem = $order->items()->create([
                            'product_id' => $itemData['product_id'],
                            'qty' => $itemData['qty_delivered'],
                            'unit_id' => $itemData['unit_id'],
                            'unit_price' => 0, // Pending PO/Invoice
                            'subtotal' => 0,
                        ]);
                    }

                    $do->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $soItem->product_id,
                        'qty_ordered' => $soItem->qty,
                        'qty_delivered' => $itemData['qty_delivered'],
                        'unit_id' => $soItem->unit_id,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // Force Status Check for Direct DO
                if (!$request->sales_order_id) {
                    $order->refresh();
                    // Even if items make it look "Delivered", for Direct DO we must keep it "Waiting PO"
                    // until the user updates it with real PO.
                    if ($order->status !== SalesOrder::STATUS_WAITING_PO) {
                        $order->status = SalesOrder::STATUS_WAITING_PO;
                        $order->save();
                    }
                }

                return redirect()->route('sales.deliveries.show', $do->id)->with('success', 'Delivery Order created successfully.');
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat DO: ' . $e->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder): Response
    {
        $deliveryOrder->load(['salesOrder.customer', 'warehouse', 'items.product', 'items.unit', 'items.salesOrderItem']);

        // Hide expensive appends
        $deliveryOrder->items->each->makeHidden(['current_stock']);

        // Fetch shipment primary DO if shipment_number is set
        $primaryDo = null;
        if ($deliveryOrder->shipment_number) {
            $primaryDo = DeliveryOrder::where('shipment_number', $deliveryOrder->shipment_number)
                ->orderBy('id')
                ->first();
        }

        return Inertia::render('Sales/Deliveries/Show', [
            'deliveryOrder' => $deliveryOrder,
            'primaryDo' => $primaryDo,
            'vehicles' => Vehicle::where('is_active', true)->whereIn('usage_type', ['logistics', 'both'])->orderBy('license_plate')->get(),
        ]);
    }

    public function print(Request $request, DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit']);
        $format = $request->query('format', 'a4'); // Default to A4 if not provided
        return view('print.surat-jalan', [
            'order' => $deliveryOrder,
            'format' => $format,
        ]);
    }

    public function publicValidate($uuid)
    {
        if (Str::isUuid($uuid)) {
            $order = DeliveryOrder::with(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit'])
                ->where('public_uuid', $uuid)
                ->firstOrFail();
        } else {
            $order = DeliveryOrder::with(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit'])
                ->where('id', $uuid)
                ->firstOrFail();

            if (!empty($order->public_uuid)) {
                return redirect()->route('sales.deliveries.public-validate', $order->public_uuid);
            }
        }

        return view('print.public-delivery-validation', ['order' => $order]);
    }

    public function complete(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status === DeliveryOrder::STATUS_COMPLETED) {
            return back()->with('error', 'Delivery Order is already completed.');
        }

        try {
            \DB::transaction(function () use ($deliveryOrder) {
                $deliveryOrder->complete();
                
                // If SO is Waiting PO, keep DO as DELIVERED (Stock deducted, but admin verification pending PO)
                // Otherwise, mark as COMPLETED
                $so = $deliveryOrder->salesOrder;
                if ($so && $so->status === \App\Models\SalesOrder::STATUS_WAITING_PO) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_DELIVERED;
                } else {
                    $deliveryOrder->status = DeliveryOrder::STATUS_COMPLETED;
                }
                
                $deliveryOrder->save();

                // Deduct stock using shared helper
                $this->deductStock($deliveryOrder);
            });
            return back()->with('success', 'Delivery Order verified and stock deducted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error completing delivery: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,picking,packed,shipped,delivered,completed',
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $deliveryOrder->status;

        // Logic restrictions
        if ($newStatus === DeliveryOrder::STATUS_COMPLETED) {
            return $this->complete($deliveryOrder);
        }

        // Define status groups
        // 'Deducted' group: Statuses where stock SHOULD be deducted
        // According to new requirement: SHIPPED, DELIVERED, COMPLETED
        $deductedStatuses = [
            DeliveryOrder::STATUS_SHIPPED, 
            DeliveryOrder::STATUS_DELIVERED, 
            DeliveryOrder::STATUS_COMPLETED
        ];

        // 'Not Deducted' group: DRAFT, PICKING, PACKED
        $notDeductedStatuses = [
            DeliveryOrder::STATUS_DRAFT, 
            DeliveryOrder::STATUS_PICKING, 
            DeliveryOrder::STATUS_PACKED
        ];

        DB::transaction(function () use ($deliveryOrder, $newStatus, $oldStatus, $deductedStatuses, $notDeductedStatuses) {
            
            // ROBUST LOGIC: Always enforce state based on New Status
            
            // 1. If New Status requires Deduction (Shipped, Delivered, Completed)
            if (in_array($newStatus, $deductedStatuses)) {
                // This method is now idempotent: it will only deduct if not already deducted.
                // This covers: 
                // - Normal flow (Draft -> Shipped)
                // - Re-ship flow (Packed -> Shipped)
                // - Repair flow (Black Hole Shipped -> Delivered)
                $this->deductStock($deliveryOrder);
            }
            
            // 2. If New Status requires NO Deduction (Draft, Picking, Packed)
            // AND the Old Status WAS Deducted
            elseif (in_array($newStatus, $notDeductedStatuses) && in_array($oldStatus, $deductedStatuses)) {
                // Only restore if we are moving back from a deducted state
                $this->restoreStock($deliveryOrder);
            }

            // Update Status
            $deliveryOrder->status = $newStatus;
            
            // Update timestamps
            if ($newStatus === DeliveryOrder::STATUS_DELIVERED && !$deliveryOrder->delivered_at) {
                // If moving directly to delivered (unlikely via updateStatus but possible)
                // Actually delivered_at usually set at complete/verified or driver action.
                // Let's keep it null here unless explicitly setting it? 
                // Driver app might set it. For admin panel status update, we might leave it.
            }
            
            if (in_array($newStatus, $notDeductedStatuses)) {
                 $deliveryOrder->delivered_at = null;
            }

            $deliveryOrder->save();
             
            // Force Recalculate Sales Order Items because DO Status change affects qty_delivered totals
            // (e.g. moving from Packed -> Shipped adds to qty_delivered)
            foreach ($deliveryOrder->items as $item) {
                if ($item->salesOrderItem) {
                    $item->salesOrderItem->recalculateTotals();
                }
            }
             
             // Update SO Status if needed
             // If reverting from Delivered/Completed to something else, check SO.
             if ($deliveryOrder->salesOrder && $deliveryOrder->salesOrder->status === \App\Models\SalesOrder::STATUS_DELIVERED) {
                 if (in_array($newStatus, $notDeductedStatuses)) {
                      $deliveryOrder->salesOrder->status = \App\Models\SalesOrder::STATUS_PROCESSING;
                      $deliveryOrder->salesOrder->save();
                 }
             }
        });

        return back()->with('success', "Delivery Order status updated to {$newStatus}.");
    }



    /**
     * Helper to restore stock safely
     */
    public function updateItems(Request $request, DeliveryOrder $deliveryOrder)
    {
        if (!in_array($deliveryOrder->status, ['draft', 'delivered', 'completed'])) {
            return back()->with('error', 'Only draft, delivered, or completed deliveries can be updated.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'delivery_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:delivery_order_items,id',
            'items.*.qty_delivered' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $deliveryOrder->update([
            'vehicle_id' => $validated['vehicle_id'],
            'vehicle_number' => $validated['vehicle_number'],
            'driver_name' => $validated['driver_name'],
            'delivery_date' => $validated['delivery_date'],
        ]);

        // Handle Revision Numbering
        // Only increment revision if status is DELIVERED or COMPLETED (meaning it was finalized)
        $status = $deliveryOrder->status;
        $isRevision = in_array($status, ['delivered', 'completed']);

        if ($isRevision) {
            $currentRevision = $deliveryOrder->revision;
            $newRevision = $currentRevision + 1;
            
            // Strip existing REV suffix if exists to get base number
            // Pattern: ...-REV-X
            $baseNumber = preg_replace('/-REV-\d+$/', '', $deliveryOrder->do_number);
            $newDoNumber = "{$baseNumber}-REV-{$newRevision}";

            $deliveryOrder->update([
                'revision' => $newRevision,
                'do_number' => $newDoNumber
            ]);
        }
        
        foreach ($validated['items'] as $itemData) {
            $item = \App\Models\DeliveryOrderItem::with('salesOrderItem')->find($itemData['id']);
            
            $oldQty = $item->qty_delivered;
            $newQty = $itemData['qty_delivered'];
            $qtyDiff = $newQty - $oldQty;
            
            // Validation: Cannot deliver more than SO Qty (ROBUST CHECK)
            // We verify against REAL database aggregation, not the cached 'qty_delivered' column on SO Item
            // because the cached column might be out of sync (the "Black Hole" bug).
            
            if ($qtyDiff > 0) {
                 $soItem = $item->salesOrderItem;
                 
                 // 1. Calculate Real Delivered Total (Excluding current DO Item being edited)
                 // We sum all DO Items linked to this SO Item that are NOT Cancelled.
                 // We exclude the current DO Item ID because we will add the NEW qty later.
                 $realDeliveredOther = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                    ->where('id', '!=', $item->id)
                    ->whereHas('deliveryOrder', function($q) {
                        $q->where('status', '!=', 'cancelled');
                    })
                    ->sum('qty_delivered');
                 
                 // 2. Calculate Returned Qty
                 $qtyReturned = $soItem->returnItems()->sum('qty');
                 
                 // 3. Gross Delivered (Others + New Proposal)
                 $grossDeliveredProposed = $realDeliveredOther + $newQty;
                 
                 // 4. Net Delivered (Gross - Returned)
                 $netDeliveredProposed = $grossDeliveredProposed - $qtyReturned;
                 
                 // 5. Remaining after Proposal
                 $remainingAfter = $soItem->qty - $netDeliveredProposed;
                 
                 if ($remainingAfter < -0.0001) { // Float tolerance
                     $maxAllowed = $soItem->qty - ($realDeliveredOther - $qtyReturned);
                     return back()->with('error', "Gagal: Penambahan jumlah untuk [{$item->product->name}] melebihi pesanan. Qty Order: {$soItem->qty}, Sudah Dikirim (Real): {$realDeliveredOther}, Sisa Maksimum: {$maxAllowed}.");
                 }
            }

            $item->update([
                'qty_delivered' => $newQty,
                'notes' => $itemData['notes'] ?? null,
            ]);

            // Adjust stock and sales order item if revising a completed/delivered order
            if ($isRevision && $qtyDiff != 0) {
                // Adjust Stock: If qty increases (+), stock decreases (-). So we pass NEGATIVE diff.
                // If qty decreases (-), stock increases (+). So we pass NEGATIVE diff (which becomes positive).
                $stock = \App\Models\ProductStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $deliveryOrder->warehouse_id)
                    ->first();

                if ($stock) {
                    $stock->adjustStock(
                        -$qtyDiff, 
                        null, 
                        \App\Models\StockMovement::TYPE_SO_DELIVERY, 
                        $deliveryOrder, 
                        "DO Rev #{$newRevision} (Qty Diff: {$qtyDiff})"
                    );
                }

                // Update Sales Order Item Delivered Qty
                if ($item->salesOrderItem) {
                    $item->salesOrderItem->recalculateTotals();
                }
            }
        }

        return back()->with('success', 'Delivery Order updated successfully.');
    }

    public function destroyItem(\App\Models\DeliveryOrderItem $item)
    {
        if ($item->deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only items in draft deliveries can be removed.');
        }

        // Prevent deleting the last item
        if ($item->deliveryOrder->items()->count() <= 1) {
            return back()->with('error', 'A Delivery Order must have at least one item. Cancel the DO if you want to remove all items.');
        }

        $item->delete();

        return back()->with('success', 'Item removed from delivery.');
    }

    public function createInvoice(DeliveryOrder $deliveryOrder)
    {
        if (!in_array($deliveryOrder->status, ['completed', 'delivered'])) {
            return back()->with('error', 'Only verified (delivered/completed) deliveries can be invoiced.');
        }

        // Check if already invoiced (simple check for now)
        // You might want to add a relationship or flag in DO to track this properly

        try {
            return \DB::transaction(function () use ($deliveryOrder) {
                $so = $deliveryOrder->salesOrder;
                $invoiceDate = $deliveryOrder->delivery_date ? Carbon::parse($deliveryOrder->delivery_date) : now();
                
                $invoice = \App\Models\SalesInvoice::create([
                    'company_id' => $deliveryOrder->company_id,
                    'invoice_number' => \App\Models\SalesInvoice::generateInvoiceNumber($deliveryOrder->customer),
                    'sales_order_id' => $so->id,
                    'customer_id' => $deliveryOrder->customer_id,
                    'invoice_date' => $invoiceDate,
                    'due_date' => (clone $invoiceDate)->addDays($deliveryOrder->customer?->payment_days ?? 30),
                    'status' => 'draft',
                    'subtotal' => 0, // Will be calculated
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => 0,
                    'paid_amount' => 0,
                    'balance' => 0,
                    'created_by' => auth()->id(),
                ]);

                $subtotal = 0;
                foreach ($deliveryOrder->items as $item) {
                    $soItem = $item->salesOrderItem;
                    $itemAmount = $item->qty_delivered * $soItem->unit_price;
                    $discountAmt = $itemAmount * ($soItem->discount_percent / 100);
                    $lineTotal = $itemAmount - $discountAmt;

                    $invoice->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $item->product_id,
                        'description' => $soItem->product->name ?? $soItem->description,
                        'qty' => $item->qty_delivered,
                        'unit_id' => $item->unit_id,
                        'unit_price' => $soItem->unit_price,
                        'discount_percent' => $soItem->discount_percent,
                        'discount_amount' => $discountAmt,
                        'subtotal' => $lineTotal,
                        'delivery_order_id' => $deliveryOrder->id,
                    ]);

                    $subtotal += $lineTotal;

                    // Update DO item invoiced qty
                    $item->recalculateInvoiced();

                    // Update SO item invoiced qty
                    if ($soItem) { 
                        $soItem->recalculateInvoiced();
                    }
                }

                $deliveryOrder->refreshInvoiceStatus();

                // Update invoice totals
                $taxAmount = $subtotal * ($so->tax_percent / 100);
                $total = $subtotal + $taxAmount;

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'balance' => $total,
                ]);

                return redirect()->route('sales.invoices.index')->with('success', 'Invoice created from Delivery Order.');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    public function bulkInvoicePreview(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'exists:delivery_orders,id',
            'select_all' => 'nullable|boolean',
            'filters' => 'nullable|array',
        ]);

        $deliveryOrders = $this->getEligibleBulkDOs($validated);

        if ($deliveryOrders->isEmpty()) {
            return response()->json(['error' => 'No eligible Delivery Orders found.'], 422);
        }

        $groups = $deliveryOrders->groupBy('sales_order_id');
        $previewData = [];

        foreach ($groups as $soId => $dos) {
            $so = $dos->first()->salesOrder;
            $customer = $so->customer;
            
            $totalQty = 0;
            $totalAmount = 0;
            $items = [];

            // Group items for this invoice
            $groupedItems = [];
            foreach ($dos as $do) {
                foreach ($do->items as $item) {
                    $unitPrice = (float) ($item->salesOrderItem->unit_price ?? 0);
                    $discountPercent = (float) ($item->salesOrderItem->discount_percent ?? 0);
                    $key = $item->product_id . '_' . round($unitPrice, 2) . '_' . round($discountPercent, 2);

                    if (!isset($groupedItems[$key])) {
                        $groupedItems[$key] = [
                            'product_name' => $item->product->name,
                            'qty' => 0,
                            'unit_name' => $item->unit->short_name ?? $item->unit->name ?? '',
                            'unit_price' => $unitPrice,
                            'discount_percent' => $discountPercent,
                        ];
                    }
                    $qty = (float) $item->qty_delivered;
                    $groupedItems[$key]['qty'] += $qty;
                    $totalQty += $qty;
                    
                    $lineTotal = $qty * $unitPrice * (1 - ($discountPercent / 100));
                    $totalAmount += $lineTotal;
                }
            }

            $previewData[] = [
                'so_id' => $so->id,
                'so_number' => $so->so_number,
                'customer_name' => $customer->name,
                'do_count' => $dos->count(),
                'do_numbers' => $dos->pluck('do_number')->implode(', '),
                'total_qty' => $totalQty,
                'total_amount' => $totalAmount,
                'items' => array_values($groupedItems)
            ];
        }

        return response()->json([
            'invoices_count' => count($previewData),
            'total_dos' => $deliveryOrders->count(),
            'preview' => $previewData
        ]);
    }

    private function getEligibleBulkDOs(array $validated)
    {
        if (!empty($validated['select_all'])) {
            $query = DeliveryOrder::with(['items.salesOrderItem.product', 'items.unit', 'salesOrder.customer'])
                ->whereIn('status', ['completed', 'delivered', 'shipped'])
                ->invoiceStatus('pending');
            
            $filters = $validated['filters'] ?? [];
            $query->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                      ->orWhere('shipping_name', 'like', "%{$search}%")
                      ->orWhereHas('salesOrder', function ($sq) use ($search) {
                          $sq->where('so_number', 'like', "%{$search}%")
                             ->orWhereHas('customer', function ($cq) use ($search) {
                                 $cq->where('name', 'like', "%{$search}%");
                             });
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['customer'] ?? null, function ($q, $customer) {
                $q->where('customer_id', $customer);
            })
            ->when($filters['delivery_date_from'] ?? null, function ($q, $date) {
                $q->whereDate('delivery_date', '>=', $date);
            })
            ->when($filters['delivery_date_to'] ?? null, function ($q, $date) {
                $q->whereDate('delivery_date', '<=', $date);
            })
            ->when($filters['invoice_status'] ?? null, function ($q, $status) {
                $q->invoiceStatus($status);
            });

            return $query->get();
        }

        return DeliveryOrder::with(['items.salesOrderItem.product', 'items.unit', 'salesOrder.customer'])
            ->whereIn('id', $validated['ids'] ?? [])
            ->whereIn('status', ['completed', 'delivered', 'shipped'])
            ->invoiceStatus('pending')
            ->get();
    }

    public function bulkInvoice(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'exists:delivery_orders,id',
            'select_all' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'excluded_so_ids' => 'nullable|array',
            'excluded_so_ids.*' => 'integer'
        ]);

        $deliveryOrders = $this->getEligibleBulkDOs($validated);

        if ($deliveryOrders->isEmpty()) {
            return back()->with('error', 'No Delivery Orders found.');
        }

        // Group by Sales Order for batch processing
        $excludedSoIds = $validated['excluded_so_ids'] ?? [];
        $groups = $deliveryOrders->groupBy('sales_order_id')
            ->reject(function($dos, $soId) use ($excludedSoIds) {
                return in_array($soId, $excludedSoIds);
            });
            
        $invoiceCount = 0;
        $processedDOs = 0;
        $lastInvoiceId = null;

        try {
            DB::transaction(function () use ($groups, &$invoiceCount, &$processedDOs, &$lastInvoiceId, $deliveryOrders) {
                // Old sequence logic removed. Using SalesInvoice::generateInvoiceNumber() inside loop.

                foreach ($groups as $soId => $dos) {
                    $groupedItems = [];
                    $so = $dos->first()->salesOrder;
                    $customerId = $so->customer_id;

                    foreach ($dos as $do) {
                        foreach ($do->items as $item) {
                            $unitPrice = (float) ($item->salesOrderItem->unit_price ?? 0);
                            $discountPercent = (float) ($item->salesOrderItem->discount_percent ?? 0);
                            // Include DO Number in key to prevent merging items from different DOs
                            $key = $item->product_id . '_' . round($unitPrice, 2) . '_' . round($discountPercent, 2) . '_' . $do->do_number;
                            
                            if (!isset($groupedItems[$key])) {
                                $groupedItems[$key] = [
                                    'product_id' => $item->product_id,
                                    'sales_order_item_id' => $item->sales_order_item_id,
                                    'description' => $item->product->name,
                                    'qty' => 0,
                                    'unit_id' => $item->unit_id,
                                    'unit_price' => $unitPrice,
                                    'discount_percent' => $discountPercent,
                                    'do_numbers' => []
                                ];
                            }
                            
                            $groupedItems[$key]['qty'] += (float) $item->qty_delivered;
                            if (!in_array($do->do_number, $groupedItems[$key]['do_numbers'])) {
                                $groupedItems[$key]['do_numbers'][] = $do->do_number;
                            }

                            $item->recalculateInvoiced();

                            if ($item->salesOrderItem) {
                                $item->salesOrderItem->recalculateInvoiced();
                            }
                        }
                        $processedDOs++;
                        $do->refreshInvoiceStatus();
                    }

                    if (empty($groupedItems)) continue;

                    $invoiceDate = ($dos->max('delivery_date') ? Carbon::parse($dos->max('delivery_date')) : now());
                    $invoiceNumber = \App\Models\SalesInvoice::generateInvoiceNumber($so->customer);
                    $invoice = \App\Models\SalesInvoice::create([
                        'company_id' => $so->company_id,
                        'invoice_number' => $invoiceNumber,
                        'sales_order_id' => $so->id,
                        'customer_id' => $customerId,
                        'invoice_date' => $invoiceDate,
                        'due_date' => (clone $invoiceDate)->addDays($so->customer?->payment_days ?? 30),
                        'status' => 'draft',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'total' => 0,
                        'balance' => 0,
                        'created_by' => auth()->id(),
                        'notes' => 'Batch Generated from DOs: ' . $dos->pluck('do_number')->implode(', '),
                    ]);

                    foreach ($groupedItems as $itemData) {
                        \App\Models\SalesInvoiceItem::create([
                            'sales_invoice_id' => $invoice->id,
                            'sales_order_item_id' => $itemData['sales_order_item_id'],
                            'product_id' => $itemData['product_id'],
                            'description' => $itemData['description'],
                            'qty' => $itemData['qty'],
                            'unit_id' => $itemData['unit_id'],
                            'unit_price' => $itemData['unit_price'],
                            'discount_percent' => $itemData['discount_percent'],
                            'delivery_order_id' => $deliveryOrders->where('do_number', $itemData['do_numbers'][0])->first()->id ?? null,
                        ]);
                    }

                    $invoice->refresh();
                    $invoiceCount++;
                    $lastInvoiceId = $invoice->id;
                }
            });

            if ($invoiceCount === 0) {
                return back()->with('error', 'No eligible Delivery Orders were processed.');
            }

            if ($invoiceCount === 1) {
                return redirect()->route('sales.invoices.show', $lastInvoiceId)->with('success', 'Invoice generated successfully.');
            }

            return redirect()->route('sales.invoices.index')->with('success', "Batch Processing Complete: Generated $invoiceCount invoices from $processedDOs Delivery Orders.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating batch invoices: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new DeliveryOrderExport, 'delivery_orders_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new DeliveryOrderImport($request->boolean('overwrite'));
            Excel::import($import, $request->file('file'));
            
            $msg = "Import selesai. Created: {$import->importedCount}, Updated: {$import->updatedCount}, Skipped: {$import->skippedCount}.";
            if (!empty($import->errors)) {
                $msg .= " Beberapa issues: " . implode(', ', array_slice($import->errors, 0, 3));
                if (count($import->errors) > 3) $msg .= " (dan lainnya)";
                return back()->with('warning', $msg);
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\DeliveryOrderDataExport, 'delivery_orders_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new DeliveryOrderTemplateExport, 'delivery_orders_import_template.xlsx');
    }
    private function getRomanMonth($month)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[(int)$month] ?? 'I';
    }




    /**
     * Helper to deduct stock safely (prevents double deduction)
     */
    private function deductStock(DeliveryOrder $deliveryOrder)
    {
        // Check if already deducted AND not reverted
        $deductionCount = \App\Models\StockMovement::where('reference_type', get_class($deliveryOrder))
            ->where('reference_id', $deliveryOrder->id)
            ->where('type', \App\Models\StockMovement::TYPE_SO_DELIVERY)
            ->count();

        $reversalCount = \App\Models\StockMovement::where('reference_type', get_class($deliveryOrder))
            ->where('reference_id', $deliveryOrder->id)
            ->whereIn('type', [\App\Models\StockMovement::TYPE_CORRECTION, \App\Models\StockMovement::TYPE_ADJUSTMENT]) // Assuming Revert uses Correction
            ->where('notes', 'like', "Revert DO #{$deliveryOrder->do_number}%") // Safer check
            ->count();

        // If net deductions > reversals, it means it's currently deducted.
        if ($deductionCount > $reversalCount) return;

        foreach ($deliveryOrder->items as $item) {
            // Update SO item delivered qty ATOMICALLY
            $soItem = $item->salesOrderItem;
            if ($soItem) {
                // Use recalculateTotals to ensure consistency with actual DO statuses
                $soItem->recalculateTotals();
            }

            // Reduce product stock
            $stock = \App\Models\ProductStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $deliveryOrder->warehouse_id)
                ->first();

            if ($stock) {
                $stock->adjustStock(
                    -$item->qty_delivered,
                    null,
                    \App\Models\StockMovement::TYPE_SO_DELIVERY,
                    $deliveryOrder,
                    "Delivery Order #{$deliveryOrder->do_number} (Shipped)"
                );
            }
        }
    }

    /**
     * Helper to restore stock safely
     */
    private function restoreStock(DeliveryOrder $deliveryOrder)
    {
        // Check if was deducted (to reverse physical stock)
        // Also check if ALREADY REVERTED to prevent double reversal
        $deductionCount = \App\Models\StockMovement::where('reference_type', get_class($deliveryOrder))
            ->where('reference_id', $deliveryOrder->id)
            ->where('type', \App\Models\StockMovement::TYPE_SO_DELIVERY)
            ->count();

        $reversalCount = \App\Models\StockMovement::where('reference_type', get_class($deliveryOrder))
            ->where('reference_id', $deliveryOrder->id)
            ->whereIn('type', [\App\Models\StockMovement::TYPE_CORRECTION, \App\Models\StockMovement::TYPE_ADJUSTMENT]) 
            ->where('notes', 'like', "Revert DO #{$deliveryOrder->do_number}%")
            ->count();

        // Only restore if Net Deducted (Deductions > Reversals)
        if ($deductionCount <= $reversalCount) return;

        foreach ($deliveryOrder->items as $item) {
            // 1. Restore Physical Stock
            $stock = \App\Models\ProductStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $deliveryOrder->warehouse_id)
                ->first();

            if ($stock) {
                $stock->adjustStock(
                    (float) $item->qty_delivered,
                    null,
                    \App\Models\StockMovement::TYPE_CORRECTION,
                    $deliveryOrder,
                    "Revert DO #{$deliveryOrder->do_number}"
                );
            }

            // 2. Recalculate Sales Order Item Delivered Qty
            if ($item->salesOrderItem) {
                // Use recalculateTotals to ensure consistency
                $item->salesOrderItem->recalculateTotals();
            }
        }
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Hanya Delivery Order berstatus Draft yang dapat dihapus.');
        }
        
        try {
            DB::transaction(function () use ($deliveryOrder) {
                // 1. Delete all items
                $deliveryOrder->items()->delete();
                
                // 2. Delete the DO itself
                $deliveryOrder->delete();
                
                // Note: We do NOT delete the Sales Order even if it was a Direct DO.
                // The Sales Order remains as "Waiting PO" (if applicable) or just an empty SO.
                // Users can delete the SO separately if they want.
            });
            
            return redirect()->route('sales.deliveries.index')->with('success', 'Delivery Order berhasil dihapus.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus DO: ' . $e->getMessage());
        }
    }

    public function disburseTravelAllowance(Request $request, $shipmentNumber)
    {
        DeliveryOrder::where('shipment_number', $shipmentNumber)
            ->where('travel_allowance_status', 'requested')
            ->update(['travel_allowance_status' => 'paid']);

        return redirect()->back()->with('success', 'Uang jalan untuk Shipment ' . $shipmentNumber . ' berhasil dicairkan! 💸');
    }
}
