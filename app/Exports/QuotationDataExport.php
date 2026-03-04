<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class QuotationDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Quotation::with(['customer', 'items.product'])
            ->orderBy('quotation_date', 'desc')
            ->get()
            ->flatMap(function ($q) {
                if ($q->items->isEmpty()) {
                    return [];
                }
                return $q->items->map(function ($item) use ($q) {
                    return [
                        $q->customer ? $q->customer->code : '',
                        $q->quotation_date ? $q->quotation_date->format('Y-m-d') : '',
                        $q->valid_until ? $q->valid_until->format('Y-m-d') : '',
                        $item->product ? $item->product->sku : '',
                        $item->qty,
                        $item->unit_price,
                        $q->notes,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Quotation Date',
            'Valid Until',
            'Product Code',
            'Qty',
            'Unit Price',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getComment('A1')->getText()->createTextRun("Required. Must match an existing Customer Code.");
                $sheet->getComment('B1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Customer Code + Quotation Date will be grouped into one Quotation.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. Format: YYYY-MM-DD. Must be after Quotation Date.");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Must match an existing Product SKU.");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Minimum: 0.0001");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Unit price in base currency.");

                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('G1')->getFont()->setBold(true);
            },
        ];
    }
}
