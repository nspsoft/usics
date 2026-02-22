<?php

namespace App\Exports;

use App\Models\DeliveryOrderItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryOrderItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return DeliveryOrderItem::query()
            ->with(['deliveryOrder.customer', 'deliveryOrder.warehouse', 'product', 'unit'])
            ->leftJoin('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->select('delivery_order_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
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
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('delivery_orders.status', $status);
            })
            ->when($this->filters['customer'] ?? null, function ($q, $customer) {
                $q->where('delivery_orders.customer_id', $customer);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('delivery_orders.delivery_date', $range);
                }
            })
            ->orderBy('delivery_orders.delivery_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'DO Number',
            'Delivery Date',
            'Customer',
            'PO Number',
            'Product SKU',
            'Product Name',
            'Qty DO',
            'Qty Act',
            'Unit',
            'Delay',
            'Notes / Problem',
            'Loaded',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->deliveryOrder->do_number,
            $item->deliveryOrder->delivery_date ? $item->deliveryOrder->delivery_date->format('Y-m-d') : '-',
            $item->deliveryOrder->customer->name ?? '-',
            $item->deliveryOrder->customer_po_number ?? '-',
            $item->product->sku ?? '-',
            $item->product->name ?? '-',
            $item->qty_ordered,
            $item->qty_delivered,
            $item->unit->name ?? '-',
            $item->qty_delivered - $item->qty_ordered,
            $item->notes,
            $item->is_loaded ? 'Yes' : 'No',
            ucfirst($item->deliveryOrder->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
