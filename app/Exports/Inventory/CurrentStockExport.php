<?php

namespace App\Exports\Inventory;

use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

class CurrentStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $filters = $this->filters;
        $search = $filters['search'] ?? null;
        $warehouseId = $filters['warehouse_id'] ?? null;
        $category = $filters['category'] ?? null;
        $action = $filters['action'] ?? null;

        $availableExpr = '(SUM(qty_on_hand) - SUM(qty_reserved))';
        $hasMinStock = \Illuminate\Support\Facades\Schema::hasColumn('products', 'min_stock');
        $hasReorderPoint = \Illuminate\Support\Facades\Schema::hasColumn('products', 'reorder_point');

        $minStockCol = 'MAX(products.min_stock)';
        $reorderPointCol = 'MAX(products.reorder_point)';

        $minStockExpr = $hasMinStock ? "({$minStockCol} > 0 AND {$availableExpr} <= {$minStockCol})" : '0=1';
        $reorderExpr = $hasReorderPoint ? "({$reorderPointCol} > 0 AND {$availableExpr} <= {$reorderPointCol})" : '0=1';
        $urgentExpr = "({$availableExpr} < 0 OR {$minStockExpr})";

        $query = ProductStock::query()
            ->with(['product', 'product.category', 'warehouse'])
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereHas('warehouse')
            ->selectRaw('MIN(product_stocks.id) as id, product_stocks.product_id, product_stocks.warehouse_id, SUM(qty_on_hand) as qty_on_hand, SUM(qty_reserved) as qty_reserved')
            ->groupBy('product_stocks.product_id', 'product_stocks.warehouse_id')
            ->when($search, function ($q, $search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($warehouseId, function ($q, $warehouse) {
                $q->where('product_stocks.warehouse_id', $warehouse);
            })
            ->when($category, function ($q, $category) {
                $q->whereHas('product', function ($p) use ($category) {
                    $p->where('category_id', $category);
                });
            })
            ->when($action, function ($q, $action) use ($urgentExpr, $reorderExpr) {
                if ($action === 'urgent') {
                    $q->havingRaw($urgentExpr);
                    return;
                }

                if ($action === 'reorder') {
                    $q->havingRaw("NOT {$urgentExpr} AND {$reorderExpr}");
                    return;
                }

                if ($action === 'ok') {
                    $q->havingRaw("NOT {$urgentExpr} AND NOT ({$reorderExpr})");
                }
            })
            ->addSelect([
                'on_order_qty' => \App\Models\PurchaseOrderItem::selectRaw('COALESCE(SUM(qty - qty_received), 0)')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->whereColumn('purchase_order_items.product_id', 'product_stocks.product_id')
                    ->whereColumn('purchase_orders.warehouse_id', 'product_stocks.warehouse_id')
                    ->whereIn('purchase_orders.status', ['ordered', 'partial'])
            ]);

        // Sorting
        $sort = $filters['sort'] ?? 'product_name';
        $direction = $filters['direction'] ?? 'asc';

        if ($sort === 'product_name') {
            $query->orderBy('products.name', $direction);
        } elseif ($sort === 'product_sku') {
            $query->orderBy('products.sku', $direction);
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction);
        } elseif ($sort === 'qty_on_hand') {
            $query->orderBy('qty_on_hand', $direction);
        } elseif ($sort === 'available') {
            $query->orderByRaw('(SUM(qty_on_hand) - SUM(qty_reserved)) ' . $direction);
        } else {
            $query->orderBy('products.name', 'asc')
                  ->orderBy('warehouse_id', 'asc');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Type',
            'Warehouse',
            'Min Stock',
            'Reorder Point',
            'Max Stock',
            'Action / Recommendation',
            'On Order',
            'Qty On Hand',
            'Reserved',
            'Available',
        ];
    }

    public function map($stock): array
    {
        $product = $stock->product;
        
        $onHand = (float) ($stock->qty_on_hand ?? 0);
        $reserved = (float) ($stock->qty_reserved ?? 0);
        $onOrder = (float) ($stock->on_order_qty ?? 0);
        $available = $onHand - $reserved;
        $projected = $available + $onOrder;

        $minStock = (float) ($product->min_stock ?? 0);
        $maxStock = (float) ($product->max_stock ?? 0);
        $reorderPoint = (float) ($product->reorder_point ?? 0);
        $reorderQty = (float) ($product->reorder_qty ?? 0);

        // Get Reorder suggestion qty
        $qtyToOrder = 0;
        if ($reorderQty > 0) {
            $qtyToOrder = $reorderQty;
        } elseif ($maxStock > 0) {
            $qtyToOrder = $maxStock - $projected;
        } else {
            $qtyToOrder = ($minStock * 2) - $projected;
        }

        if ($maxStock > 0) {
            $spaceAvailable = $maxStock - $projected;
            $qtyToOrder = min($qtyToOrder, $spaceAvailable);
        }
        $qtyToOrder = max(0, $qtyToOrder);

        // Get recommendation label
        $action = 'Stock OK';
        if ($available < 0 || ($minStock > 0 && $available <= $minStock)) {
            $finalQty = ($available < 0 && $qtyToOrder == 0) ? abs($available) : $qtyToOrder;
            $action = 'URGENT: Reorder ' . number_format($finalQty);
        } elseif ($reorderPoint > 0 && $available <= $reorderPoint) {
            $action = 'Reorder ' . number_format($qtyToOrder);
        }

        // Product Type labels
        $productTypeLabels = [
            'raw_material' => 'Raw Material',
            'wip' => 'WIP',
            'finished_good' => 'Finished Good',
            'spare_part' => 'Spare Part',
        ];
        $typeLabel = $productTypeLabels[$product->product_type ?? ''] ?? ($product->product_type ?? '');

        return [
            $product->sku ?? '-',
            $product->name ?? '-',
            $product->category->name ?? '-',
            $typeLabel,
            $stock->warehouse->name ?? '-',
            $minStock,
            $reorderPoint,
            $maxStock,
            $action,
            $onOrder,
            $onHand,
            $reserved,
            $available,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEFEFEF'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'CURRENT STOCK REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                
                $cellRange = 'A4:M' . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }
}
