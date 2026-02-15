<?php

namespace App\Exports;

use App\Models\SalesOrderItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesOrderItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return SalesOrderItem::query()
            ->with(['salesOrder.customer', 'salesOrder.warehouse', 'product', 'unit'])
            ->leftJoin('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select('sales_order_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
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
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('sales_orders.status', $status);
            })
            ->when($this->filters['customer'] ?? null, function ($q, $customer) {
                $q->where('sales_orders.customer_id', $customer);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('sales_orders.order_date', $range);
                }
            })
            ->orderBy('sales_orders.order_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Order Date',
            'Customer',
            'No PO',
            'Product SKU',
            'Product Name',
            'Qty Ordered',
            'Qty Delivered',
            'Qty Returned',
            'Balance (Outstanding)',
            'Qty Invoiced',
            'Unit',
            'Unit Price',
            'Total Price',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->salesOrder->so_number,
            $item->salesOrder->order_date ? $item->salesOrder->order_date->format('Y-m-d') : '-',
            $item->salesOrder->customer->name ?? '-',
            $item->salesOrder->customer_po_number ?? '-',
            $item->product->sku ?? '-',
            $item->product->name ?? '-',
            $item->qty,
            $item->qty_delivered,
            $item->qty_returned,
            $item->remaining_qty,
            $item->qty_invoiced,
            $item->unit->name ?? '-',
            $item->unit_price,
            $item->subtotal,
            ucfirst($item->salesOrder->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
