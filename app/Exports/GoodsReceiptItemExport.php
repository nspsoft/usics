<?php

namespace App\Exports;

use App\Models\GoodsReceiptItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return GoodsReceiptItem::query()
            ->with(['goodsReceipt.supplier', 'goodsReceipt.warehouse', 'goodsReceipt.purchaseOrder', 'product', 'unit'])
            ->leftJoin('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->select('goods_receipt_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('goodsReceipt', function ($gr) use ($search) {
                        $gr->where('grn_number', 'like', "%{$search}%")
                           ->orWhere('delivery_note_number', 'like', "%{$search}%")
                           ->orWhereHas('supplier', function ($s) use ($search) {
                               $s->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('goods_receipts.status', $status);
            })
            ->when($this->filters['supplier'] ?? null, function ($q, $supplier) {
                $q->where('goods_receipts.supplier_id', $supplier);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('goods_receipts.receipt_date', $range);
                }
            })
            ->orderBy('goods_receipts.receipt_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'Receipt Date',
            'PO Number',
            'Supplier',
            'Delivery Note',
            'Product Code',
            'Product Name',
            'Qty Ordered',
            'Qty Received',
            'Qty Rejected',
            'Qty Accepted',
            'Unit',
            'Unit Cost',
            'Total Value',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->goodsReceipt->grn_number,
            $item->goodsReceipt->receipt_date ? $item->goodsReceipt->receipt_date->format('Y-m-d') : '-',
            $item->goodsReceipt->purchaseOrder->po_number ?? '-',
            $item->goodsReceipt->supplier->name ?? '-',
            $item->goodsReceipt->delivery_note_number ?? '-',
            $item->product->code ?? $item->product->sku ?? '-',
            $item->product->name ?? '-',
            $item->qty_ordered,
            $item->qty_received,
            $item->qty_rejected,
            $item->qty_accepted,
            $item->unit->name ?? '-',
            $item->unit_cost,
            $item->total_value,
            ucfirst($item->goodsReceipt->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
