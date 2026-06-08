<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceiptItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GoodsReceiptItemExport;

class GoodsReceiptItemController extends Controller
{
    public function index(Request $request): Response
    {
        $hasProductCode = \Illuminate\Support\Facades\DB::selectOne("SHOW COLUMNS FROM products LIKE 'code'") !== null;
        $hasSupplierCode = \Illuminate\Support\Facades\DB::selectOne("SHOW COLUMNS FROM suppliers LIKE 'code'") !== null;

        $query = GoodsReceiptItem::with([
                'goodsReceipt.supplier',
                'goodsReceipt.warehouse',
                'goodsReceipt.purchaseOrder',
                'product',
                'unit',
            ])
            ->leftJoin('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->select('goods_receipt_items.*')
            ->when($request->search, function ($q, $search) use ($hasProductCode, $hasSupplierCode) {
                $q->where(function ($sub) use ($search, $hasProductCode, $hasSupplierCode) {
                    $sub->whereHas('product', function ($p) use ($search, $hasProductCode) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");

                        if ($hasProductCode) {
                            $p->orWhere('code', 'like', "%{$search}%");
                        }
                    })
                    ->orWhereHas('goodsReceipt', function ($gr) use ($search, $hasSupplierCode) {
                        $gr->where('grn_number', 'like', "%{$search}%")
                           ->orWhere('delivery_note_number', 'like', "%{$search}%")
                           ->orWhereHas('supplier', function ($s) use ($search, $hasSupplierCode) {
                               $s->where('name', 'like', "%{$search}%");

                               if ($hasSupplierCode) {
                                   $s->orWhere('code', 'like', "%{$search}%");
                               }
                           })
                           ->orWhereHas('purchaseOrder', function ($po) use ($search) {
                               $po->where('po_number', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('goods_receipts.status', $status);
            })
            ->when($request->supplier, function ($q, $supplier) {
                $q->where('goods_receipts.supplier_id', $supplier);
            })
            ->when($request->po_number, function ($q, $poNumber) {
                $q->whereHas('goodsReceipt.purchaseOrder', function ($po) use ($poNumber) {
                    $po->where('po_number', 'like', "%{$poNumber}%");
                });
            })
            ->when($request->date_range, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('goods_receipts.receipt_date', $range);
                }
            });

        $sort = $request->input('sort', 'goods_receipts.receipt_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->leftJoin('suppliers', 'goods_receipts.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction);
        } elseif ($sort === 'product_name') {
            $query->leftJoin('products', 'goods_receipt_items.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->paginate(20)->withQueryString();

        $supplierColumns = $hasSupplierCode ? ['id', 'name', 'code'] : ['id', 'name'];

        return Inertia::render('Purchasing/Reports/GoodsReceiptItems', [
            'items' => $items,
            'suppliers' => \App\Models\Supplier::orderBy('name')->get($supplierColumns),
            'filters' => $request->only(['search', 'status', 'supplier', 'po_number', 'date_range']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'dispatched', 'label' => 'Dispatched'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'inspected', 'label' => 'Inspected'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new GoodsReceiptItemExport($request->all()), 'gr_items_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
