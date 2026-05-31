<?php

namespace App\Exports;

use App\Models\StockOpnameItem;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockOpnameItemsExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $filters;
    protected bool $productsHasCodeColumn;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->productsHasCodeColumn = Schema::hasColumn('products', 'code');
    }

    public function query()
    {
        return StockOpnameItem::query()
            ->with(['product', 'opname.warehouse', 'opname.createdBy'])
            ->join('inv_stock_opnames', 'inv_stock_opname_items.stock_opname_id', '=', 'inv_stock_opnames.id')
            ->select('inv_stock_opname_items.*')
            ->whereNull('inv_stock_opnames.deleted_at')
            ->when($this->filters['opname_ids'] ?? null, function ($q, $opnameIds) {
                $q->whereIn('inv_stock_opname_items.stock_opname_id', $opnameIds);
            })
            ->when($this->filters['warehouse_id'] ?? null, function ($q, $warehouseId) {
                $q->where('inv_stock_opnames.warehouse_id', $warehouseId);
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('inv_stock_opnames.status', $status);
            })
            ->when(($this->filters['date_from'] ?? null) && ($this->filters['date_to'] ?? null), function ($q) {
                $q->whereBetween('inv_stock_opnames.opname_date', [$this->filters['date_from'], $this->filters['date_to']]);
            })
            ->orderBy('inv_stock_opnames.opname_date', 'desc')
            ->orderBy('inv_stock_opnames.opname_number', 'desc')
            ->orderBy('inv_stock_opname_items.id', 'asc');
    }

    public function headings(): array
    {
        return [
            'Opname Number',
            'Opname Date',
            'Warehouse',
            'Location',
            'Status',
            'Created By',
            'Product Code',
            'Product Name',
            'System Qty',
            'Physical Qty',
            'Difference',
            'Notes',
        ];
    }

    public function map($item): array
    {
        $opname = $item->opname;
        $product = $item->product;

        $productCode = $product?->sku ?? '';
        if ($this->productsHasCodeColumn && !empty($product?->code)) {
            $productCode = $product->code;
        }

        return [
            $opname?->opname_number ?? '',
            $opname?->opname_date ? $opname->opname_date->format('Y-m-d') : '',
            $opname?->warehouse?->name ?? '',
            $opname?->location ?? '',
            $opname?->status ?? '',
            $opname?->createdBy?->name ?? '',
            $productCode,
            $product?->name ?? '',
            $item->qty_system,
            $item->qty_physic,
            $item->qty_difference,
            $item->notes,
        ];
    }
}
