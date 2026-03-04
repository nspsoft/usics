<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesOrderDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return SalesOrder::with(['customer', 'warehouse', 'items.product', 'items.unit'])
            ->orderBy('order_date', 'desc')
            ->get()
            ->flatMap(function ($so) {
                if ($so->items->isEmpty()) {
                    return [];
                }
                return $so->items->map(function ($item) use ($so) {
                    return [
                        $so->customer ? $so->customer->code : '',
                        $so->customer_po_number,
                        $so->warehouse ? $so->warehouse->code : '',
                        $so->order_date ? $so->order_date->format('Y-m-d') : '',
                        $item->product ? $item->product->sku : '',
                        $item->qty,
                        $item->unit ? $item->unit->code : '',
                        $item->unit_price,
                        $item->discount_percent,
                        $so->notes,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer PO',
            'Warehouse Code',
            'Order Date',
            'Product Code',
            'Qty',
            'Unit Code',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getComment('A1')->getText()->createTextRun("Required. Must match an existing Customer Code.");
                $sheet->getComment('B1')->getText()->createTextRun("Optional. Customer PO number.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. Must match an existing Warehouse Code or Name.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Customer Code + PO + Order Date will be grouped into one SO.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Must match an existing Product SKU.");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Minimum: 0.0001");
                $sheet->getComment('G1')->getText()->createTextRun("Optional. Unit Code. Leave blank to use product's default unit.");
                $sheet->getComment('H1')->getText()->createTextRun("Required. Unit price in base currency.");
                $sheet->getComment('I1')->getText()->createTextRun("Optional. Discount percentage (0-100).");
                $sheet->getComment('J1')->getText()->createTextRun("Optional. Notes for the SO (taken from the first row of each group).");

                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('C1:F1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('H1')->getFont()->setBold(true)->setColor($redColor);
                
                $sheet->getStyle('B1')->getFont()->setBold(true);
                $sheet->getStyle('G1')->getFont()->setBold(true);
                $sheet->getStyle('I1:J1')->getFont()->setBold(true);
            },
        ];
    }
}
