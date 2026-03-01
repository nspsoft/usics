<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchaseOrderItemExport;

class PurchaseOrderItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PurchaseOrderItem::with([
                'purchaseOrder.supplier',
                'purchaseOrder.warehouse',
                'product',
                'unit',
            ])
            ->leftJoin('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->select('purchase_order_items.*')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($po) use ($search) {
                        $po->where('po_number', 'like', "%{$search}%")
                           ->orWhereHas('supplier', function ($s) use ($search) {
                               $s->where('name', 'like', "%{$search}%")
                                 ->orWhere('code', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('purchase_orders.status', $status);
            })
            ->when($request->supplier, function ($q, $supplier) {
                $q->where('purchase_orders.supplier_id', $supplier);
            })
            ->when($request->date_range, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('purchase_orders.order_date', $range);
                }
            });

        $sort = $request->input('sort', 'purchase_orders.order_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction);
        } elseif ($sort === 'product_name') {
            $query->leftJoin('products', 'purchase_order_items.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('Purchasing/Reports/Items', [
            'items' => $items,
            'suppliers' => \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'supplier', 'date_range']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'submitted', 'label' => 'Submitted'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'ordered', 'label' => 'Ordered'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new PurchaseOrderItemExport($request->all()), 'po_items_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
