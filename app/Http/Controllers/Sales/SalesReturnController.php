<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');

        $query = SalesReturn::with(['customer', 'warehouse', 'creator'])
            ->when($request->search, function($query, $search) {
                $query->where('number', 'like', "%{$search}%");
            });

        if ($sort === 'customer_name') {
            $query->join('customers', 'sales_returns.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('sales_returns.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'sales_returns.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('sales_returns.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $returns = $query->paginate(20)->withQueryString();

        return Inertia::render('Sales/Returns/Index', [
            'returns' => $returns,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request): Response
    {
        $salesOrder = null;
        $salesInvoice = null;

        if ($request->invoice_id) {
            $salesInvoice = SalesInvoice::with(['items.product', 'customer', 'salesOrder'])->find($request->invoice_id);
        }

        if ($request->sales_order_id) {
            $salesOrder = SalesOrder::with(['items.product', 'customer', 'warehouse'])
                ->find($request->sales_order_id);
        }

        // Fetch SOs that are delivered or have some sent quantity, or just recently delivered
        $salesOrders = SalesOrder::whereIn('status', ['delivered', 'completed', 'partial', 'shipped'])
            ->orWhereHas('deliveryOrders', function($q) {
                $q->where('status', 'delivered');
            })
            ->with(['customer', 'items'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return Inertia::render('Sales/Returns/Create', [
            'salesOrder' => $salesOrder,
            'salesInvoice' => $salesInvoice,
            'salesOrders' => $salesOrders,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function getReturnableItems(Request $request, SalesOrder $salesOrder)
    {
        // Load with DO items and Returns for calculation
        $salesOrder->load(['items.product', 'items.unit', 'deliveryOrders.items', 'returns.items']);

        $items = $salesOrder->items->map(function ($item) use ($salesOrder) {
            // Priority: Trust the summary qty_delivered on the SO item itself first
            $deliveredQty = (float) $item->qty_delivered;
            
            // If it's zero but there are DOs, try to sum them up just in case
            if ($deliveredQty <= 0) {
                $deliveredQty = $salesOrder->deliveryOrders
                    ->where('status', 'delivered')
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('qty_delivered');
            }

            // Already returned: trust qty_returned on SO Item if it exists, or sum it up
            $returnedQty = (float) $item->qty_returned;
            
            if ($returnedQty <= 0) {
                $returnedQty = $salesOrder->returns
                    ->where('status', 'confirmed')
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('qty');
            }

            $returnableQty = max(0, $deliveredQty - $returnedQty);

            if ($returnableQty <= 0) {
                return null;
            }

            return [
                'sales_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'sku' => $item->product->sku,
                'qty_delivered' => $deliveredQty,
                'qty_returned' => $returnedQty,
                'returnable_qty' => $returnableQty,
                'unit_price' => (float) $item->unit_price,
            ];
        })->filter()->values();

        return response()->json([
            'customer_id' => $salesOrder->customer_id,
            'warehouse_id' => $salesOrder->warehouse_id,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function() use ($request) {
            $lastReturn = SalesReturn::where('number', 'like', 'SRT/' . date('Ymd') . '/%')
                ->orderBy('number', 'desc')
                ->first();

            $nextSeq = 1;
            if ($lastReturn) {
                $parts = explode('/', $lastReturn->number);
                $lastSeq = (int) end($parts);
                $nextSeq = $lastSeq + 1;
            }
            $number = 'SRT/' . date('Ymd') . '/' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $salesReturn = SalesReturn::create([
                'number' => $number,
                'sales_invoice_id' => $request->sales_invoice_id,
                'sales_order_id' => $request->sales_order_id,
                'customer_id' => $request->customer_id,
                'warehouse_id' => $request->warehouse_id,
                'return_date' => $request->return_date,
                'reason' => $request->reason,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalPrice = $item['qty'] * $item['unit_price'];
                $salesReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                ]);
                $totalAmount += $totalPrice;
            }

            $salesReturn->update(['total_amount' => $totalAmount]);

            return redirect()->route('sales.returns.index')->with('success', 'Sales return created successfully.');
        });
    }

    public function show(SalesReturn $salesReturn): Response
    {
        return Inertia::render('Sales/Returns/Show', [
            'salesReturn' => $salesReturn->load(['items.product', 'customer', 'warehouse', 'creator', 'salesOrder', 'salesInvoice']),
        ]);
    }

    public function confirm(SalesReturn $salesReturn)
    {
        if ($salesReturn->status !== 'draft') {
            return back()->with('error', 'Only draft returns can be confirmed.');
        }

        return DB::transaction(function() use ($salesReturn) {
            foreach ($salesReturn->items as $item) {
                $stock = ProductStock::firstOrCreate([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $salesReturn->warehouse_id,
                ], [
                    'qty_on_hand' => 0,
                ]);

                // Increase stock for sales return
                $stock->adjustStock(
                    $item->qty,
                    $item->unit_price, // Possibly update avg cost
                    StockMovement::TYPE_SALES_RETURN,
                    $salesReturn,
                    "Sales Return: {$salesReturn->number}"
                );

                // Update SO Item qty_returned via recalculation (safe against race conditions)
                if ($salesReturn->sales_order_id) {
                    $soItem = \App\Models\SalesOrderItem::where('sales_order_id', $salesReturn->sales_order_id)
                        ->where('product_id', $item->product_id)
                        ->first();
                    
                    if ($soItem) {
                        // Recalculate from ALL confirmed returns instead of +=
                        $totalReturned = \App\Models\SalesReturn::where('sales_order_id', $salesReturn->sales_order_id)
                            ->where('status', 'confirmed')
                            ->whereHas('items', function ($q) use ($item) {
                                $q->where('product_id', $item->product_id);
                            })
                            ->get()
                            ->flatMap->items
                            ->where('product_id', $item->product_id)
                            ->sum('qty');
                        
                        // Also add current return's qty since it's not yet confirmed
                        $soItem->qty_returned = $totalReturned + $item->qty;
                        $soItem->save();
                    }
                }
            }

            $salesReturn->update(['status' => 'confirmed']);

            return back()->with('success', 'Sales return confirmed and stock updated.');
        });
    }

    public function print(SalesReturn $salesReturn)
    {
        $salesReturn->load(['items.product', 'customer', 'warehouse', 'creator', 'salesOrder', 'salesInvoice']);

        return view('print.return-grn', ['return' => $salesReturn]);
    }

    public function publicValidate($uuid)
    {
        if (Str::isUuid($uuid)) {
            $salesReturn = SalesReturn::with(['items.product', 'customer', 'warehouse'])
                ->where('public_uuid', $uuid)
                ->firstOrFail();
        } else {
            $salesReturn = SalesReturn::with(['items.product', 'customer', 'warehouse'])
                ->where('id', $uuid)
                ->firstOrFail();

            if (!empty($salesReturn->public_uuid)) {
                return redirect()->route('sales.returns.public-validate', $salesReturn->public_uuid);
            }
        }

        return view('print.public-return-validation', ['return' => $salesReturn]);
    }
}
