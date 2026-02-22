<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryOrderItemExport;

class DeliveryOrderItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeliveryOrderItem::with([
                'deliveryOrder.customer', 
                'deliveryOrder.warehouse', 
                'product', 
                'unit'
            ])
            ->leftJoin('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->select('delivery_order_items.*')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('deliveryOrder', function ($do) use ($search) {
                        $do->where('do_number', 'like', "%{$search}%")
                           ->orWhereHas('salesOrder', function ($so) use ($search) {
                               $so->where('customer_po_number', 'like', "%{$search}%");
                           })
                           ->orWhereHas('customer', function ($c) use ($search) {
                               $c->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('delivery_orders.status', $status);
            })
            ->when($request->customer, function ($q, $customer) {
                $q->where('delivery_orders.customer_id', $customer);
            })
            ->when($request->date_range, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('delivery_orders.delivery_date', $range);
                }
            });

        $sort = $request->input('sort', 'delivery_orders.delivery_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->leftJoin('customers', 'delivery_orders.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction);
        } elseif ($sort === 'product_name') {
            $query->leftJoin('products', 'delivery_order_items.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('Sales/Reports/DeliveryItems', [
            'items' => $items,
            'customers' => \App\Models\Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'customer', 'date_range']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'picking', 'label' => 'Picking'],
                ['value' => 'packed', 'label' => 'Packed'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new DeliveryOrderItemExport($request->all()), 'delivery_items_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
