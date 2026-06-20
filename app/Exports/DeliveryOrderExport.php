<?php

namespace App\Exports;

use App\Models\DeliveryOrder;
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

class DeliveryOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return DeliveryOrder::with(['salesOrder', 'customer', 'warehouse', 'items.product', 'items.unit'])
            ->orderBy('delivery_date', 'desc')
            ->get()
            ->flatMap(function ($do) {
                if ($do->items->isEmpty()) {
                    return [['order' => $do, 'item' => null]];
                }
                return $do->items->map(function ($item) use ($do) {
                    return ['order' => $do, 'item' => $item];
                });
            });
    }

    public function headings(): array
    {
        return [
            'DO Number',
            'SO Number',
            'PO Customer',
            'Customer Code',
            'Customer Name',
            'Warehouse',
            'Delivery Date',
            'Vehicle Number',
            'Driver Name',
            'Product Code',
            'Product Name',
            'Qty Ordered',
            'Qty Delivered',
            'Unit',
            'Batch Number',
            'Status',
            'Shipping Address',
        ];
    }

    public function map($row): array
    {
        $do = $row['order'];
        $item = $row['item'];

        return [
            $do->do_number,
            $do->salesOrder?->so_number,
            $do->salesOrder?->customer_po_number,
            $do->customer?->code ?? $do->salesOrder?->customer?->code,
            $do->customer?->name ?? $do->salesOrder?->customer?->name,
            $do->warehouse?->name,
            $do->delivery_date?->format('Y-m-d'),
            $do->vehicle_number,
            $do->driver_name,
            $item?->product?->sku,
            $item?->product?->name,
            $item?->qty_ordered,
            $item?->qty_delivered,
            $item?->unit?->name,
            $item?->batch_number,
            ucfirst($do->status),
            $do->shipping_address,
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

                // Title
                $sheet->mergeCells('A1:Q1');
                $sheet->setCellValue('A1', 'DELIVERY ORDER DATA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Export Date
                $sheet->mergeCells('A2:Q2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Page Setup
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Borders
                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Number format for qty columns (shifted from K/L to L/M due to PO Customer insertion)
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("L5:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M5:M{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
