<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PurchaseRequestTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                '',                 // PR Number (Blank for new)
                '2026-02-14',       // Date
                'Production',       // Department
                'John Doe',         // Requester
                'PROD-001',         // Product Code
                100,                // Quantity
                'Urgent need',      // Notes
                'Sample Item Description' // Description
            ],
            [
                '',
                '2026-02-14',
                'Production',
                'John Doe',
                'PROD-002',
                50,
                '',
                'Another Item'
            ],
            [
                '',
                '2026-02-15',
                'HR',
                'Jane Smith',
                'OFF-001',
                10,
                'Monthly supplies',
                'Paper A4'
            ],
        ]);
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
                $sheet = $event->sheet;
                $sheet->getDelegate()->getStyle('A1:H1')->getFont()->setBold(true);
                
                // Add comments
                $sheet->getComment('A1')->getText()->createTextRun("Optional/Key. Jika terisi dan opsi Overwrite dicentang, maka file akan menimpa item di PR ini. Jika dikosongkan, sistem generate PR baru.");
                $sheet->getComment('B1')->getText()->createTextRun("Required. Format: YYYY-MM-DD\nRows with same Date + Department + Requester will be grouped into one PR.");
                $sheet->getComment('C1')->getText()->createTextRun("Required. e.g. Production, HR, Maintenance");
                $sheet->getComment('D1')->getText()->createTextRun("Required. Name of the requester");
                $sheet->getComment('E1')->getText()->createTextRun("Required. Must match an existing Product Code in the system.");
                $sheet->getComment('F1')->getText()->createTextRun("Required. Numeric value.");

                // Color headers
                $redColor = new Color(Color::COLOR_RED);
                $blueColor = new Color(Color::COLOR_BLUE);
                $sheet->getDelegate()->getStyle('A1')->getFont()->setColor($blueColor);
                $sheet->getDelegate()->getStyle('B1:F1')->getFont()->setColor($redColor);
            },
        ];
    }
}
