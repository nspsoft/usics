<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CustomerTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'CUST-001',             // Code
                'PT. Maju Mundur',      // Name
                'Budi Santoso',         // Contact Person
                'regular',              // Type
                'Jl. Sudirman No. 1',   // Address
                'Jakarta',              // City
                '021-1234567',          // Phone
                'budi@example.com',     // Email
                '12.345.678.9-001.000', // Tax ID (NPWP)
                'Net 30',               // Payment Terms
                30,                     // Payment Days
            ],
            [
                'CUST-VIP-002',         // Code
                'CV. Sejahtera Abadi',  // Name
                'Siti Aminah',          // Contact Person
                'vip',                  // Type
                'Jl. Thamrin No. 99',   // Address
                'Surabaya',             // City
                '031-9876543',          // Phone
                'siti@example.com',     // Email
                '98.765.432.1-002.000', // Tax ID (NPWP)
                'Cash',                 // Payment Terms
                0,                      // Payment Days
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Contact Person',
            'Type',
            'Address',
            'City',
            'Phone',
            'Email',
            'Tax ID',
            'Payment Terms',
            'Payment Days',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments (Instructions)
                $sheet->getComment('D1')->getText()->createTextRun("Options:\n- regular\n- vip\n- wholesale");
                $sheet->getComment('J1')->getText()->createTextRun("e.g., Net 30, Cash, COD");

                // 2. Data Validation (Dropdowns)
                // Customer Type (Column D)
                $validation = $sheet->getCell('D2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"regular,vip,wholesale"');
                $sheet->setDataValidation('D2:D1000', $validation);

                // 3. Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: Code (A1), Name (B1), Type (D1), Payment Terms (J1), Payment Days (K1)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle('D1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle('J1:K1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1')->getFont()->setBold(true);
                $sheet->getStyle('E1:I1')->getFont()->setBold(true);
            },
        ];
    }
}
