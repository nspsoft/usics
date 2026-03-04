<?php

namespace App\Exports;

use App\Models\PurchaseRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PurchaseRequestDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Only export draft requests, as others shouldn't be overridden typically
        return PurchaseRequest::where('status', 'draft')
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($pr) {
                return $pr->items->map(function ($item) use ($pr) {
                    return [
                        $pr->pr_number,
                        $pr->request_date ? \Carbon\Carbon::parse($pr->request_date)->format('Y-m-d') : '',
                        $pr->department,
                        $pr->requester,
                        $item->product ? $item->product->sku : '', // Using sku as product code based on system pattern
                        $item->qty,
                        $pr->notes,
                        $item->description,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'PR Number',
            'Date',
            'Department',
            'Requester',
            'Product Code',
            'Quantity',
            'Notes',
            'Item Description',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
                
                // Add comments
                $sheet->getComment('A1')->getText()->createTextRun("Optional/Key. Jika terisi dan opsi Overwrite dicentang, maka file akan menimpa seluruh item di PR Number ini (khusus draft). Jika dikosongkan, sistem akan menggenerate PR Number baru berdasarkan tgl+dept+requester.");
                $sheet->getComment('B1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Date + Department + Requester will be grouped into one PR if PR Number is blank.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. e.g. Production, HR, Maintenance");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Name of the requester");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Must match an existing Product Code in the system.");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Numeric value.");

                // Color headers
                $redColor = new Color(Color::COLOR_RED);
                $blueColor = new Color(Color::COLOR_BLUE);
                $sheet->getStyle('A1')->getFont()->setColor($blueColor); // Distinct PR Number
                $sheet->getStyle('B1:F1')->getFont()->setColor($redColor); // Required fields
            },
        ];
    }
}
