<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CustomerDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Customer::all()->map(fn($c) => [
            $c->code,
            $c->name,
            $c->contact_person,
            $c->customer_type,
            $c->address,
            $c->city,
            $c->phone,
            $c->email,
            $c->tax_id,
            $c->payment_terms,
            $c->payment_days,
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

                // Data Validation for Type column (D)
                $validation = $sheet->getCell('D2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"regular,vip,wholesale"');
                $sheet->setDataValidation('D2:D1000', $validation);

                // Visual Cues - Mandatory Fields = Red & Bold
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
