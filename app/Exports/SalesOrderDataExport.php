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
                        $so->so_number,
                        $so->customer ? $so->customer->code : '',
                        $so->customer_po_number,
                        $so->warehouse ? $so->warehouse->code : '',
                        $so->order_date ? $so->order_date->format('Y-m-d') : '',
                        $so->delivery_date ? $so->delivery_date->format('Y-m-d') : '',
                        $item->product ? $item->product->sku : '',
                        $item->qty,
                        $item->unit ? $item->unit->code : '',
                        $item->unit_price,
                        $item->discount_percent,
                        $so->notes,
                        $so->status,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Customer Code',
            'Customer PO',
            'Warehouse Code',
            'Order Date',
            'Delivery Date',
            'Product Code',
            'Qty',
            'Unit Code',
            'Unit Price',
            'Discount %',
            'Notes',
            'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getComment('A1')->getText()->createTextRun("SO Number. Isi kolom ini untuk mode UPDATE (mengubah order yang sudah ada). Kosongkan untuk mode CREATE.");
                $sheet->getComment('B1')->getText()->createTextRun("Required. Must match an existing Customer Code.");
                $sheet->getComment('C1')->getText()->createTextRun("Optional. Customer PO number.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Must match an existing Warehouse Code or Name.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Customer Code + PO + Order Date will be grouped into one SO.");
                $sheet->getComment('F1')->getText()->createTextRun("Optional. Expected delivery date. Format: YYYY-MM-DD");
                $sheet->getComment('G1')->getText()->createTextRun("Required. Must match an existing Product SKU.");
                $sheet->getComment('H1')->getText()->createTextRun("Required. Minimum: 0.0001");
                $sheet->getComment('I1')->getText()->createTextRun("Optional. Unit Code. Leave blank to use product's default unit.");
                $sheet->getComment('J1')->getText()->createTextRun("Required. Unit price in base currency.");
                $sheet->getComment('K1')->getText()->createTextRun("Optional. Discount percentage (0-100).");
                $sheet->getComment('L1')->getText()->createTextRun("Optional. Notes for the SO.");
                $sheet->getComment('M1')->getText()->createTextRun("Info only. Status saat ini (tidak diproses saat import).");

                $blueColor = new \PhpOffice\PhpSpreadsheet\Style\Color('3B82F6');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($blueColor);

                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('D1:E1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('J1')->getFont()->setBold(true)->setColor($redColor);
                
                $sheet->getStyle('C1')->getFont()->setBold(true);
                $sheet->getStyle('F1')->getFont()->setBold(true);
                $sheet->getStyle('I1')->getFont()->setBold(true);
                $sheet->getStyle('K1:M1')->getFont()->setBold(true);
            },
        ];
    }
}
