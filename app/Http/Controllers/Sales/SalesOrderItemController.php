<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesOrderItemExport;

class SalesOrderItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesOrderItem::with([
                'salesOrder.customer', 
                'salesOrder.warehouse', 
                'product', 
                'unit',
                'deliveryOrderItems.deliveryOrder',
                'returnItems.salesReturn'
            ])
            ->leftJoin('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select('sales_order_items.*') // Avoid column name collision
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('salesOrder', function ($so) use ($search) {
                        $so->where('so_number', 'like', "%{$search}%")
                           ->orWhere('customer_po_number', 'like', "%{$search}%")
                           ->orWhereHas('customer', function ($c) use ($search) {
                               $c->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('sales_orders.status', $status);
            })
            ->when($request->customer, function ($q, $customer) {
                $q->where('sales_orders.customer_id', $customer);
            })
            ->when($request->date_range, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('sales_orders.order_date', $range);
                }
            });

        $sort = $request->input('sort', 'sales_orders.order_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction);
        } elseif ($sort === 'product_name') {
            $query->leftJoin('products', 'sales_order_items.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('Sales/Reports/Items', [
            'items' => $items,
            'customers' => \App\Models\Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'customer', 'date_range']),
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

    public function export(Request $request)
    {
        return Excel::download(new SalesOrderItemExport($request->all()), 'sales_items_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
