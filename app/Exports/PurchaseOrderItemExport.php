<?php

namespace App\Exports;

use App\Models\PurchaseOrderItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return PurchaseOrderItem::query()
            ->with(['purchaseOrder.supplier', 'purchaseOrder.warehouse', 'product', 'unit'])
            ->leftJoin('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->select('purchase_order_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($po) use ($search) {
                        $po->where('po_number', 'like', "%{$search}%")
                           ->orWhereHas('supplier', function ($s) use ($search) {
                               $s->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('purchase_orders.status', $status);
            })
            ->when($this->filters['supplier'] ?? null, function ($q, $supplier) {
                $q->where('purchase_orders.supplier_id', $supplier);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('purchase_orders.order_date', $range);
                }
            })
            ->orderBy('purchase_orders.order_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Supplier',
            'Product Code',
            'Product Name',
            'Qty Ordered',
            'Qty Received',
            'Qty Returned',
            'Balance (Outstanding)',
            'Unit',
            'Unit Price',
            'Total Price',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->purchaseOrder->po_number,
            $item->purchaseOrder->order_date ? $item->purchaseOrder->order_date->format('Y-m-d') : '-',
            $item->purchaseOrder->supplier->name ?? '-',
            $item->product->code ?? $item->product->sku ?? '-',
            $item->product->name ?? '-',
            $item->qty,
            $item->qty_received,
            $item->qty_returned,
            $item->remaining_qty,
            $item->unit->name ?? '-',
            $item->unit_price,
            $item->subtotal,
            ucfirst($item->purchaseOrder->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
