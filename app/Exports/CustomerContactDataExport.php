<?php

namespace App\Exports;

use App\Models\CustomerContact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class CustomerContactDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return CustomerContact::with('customer')->get()->map(fn($c) => [
            $c->customer ? $c->customer->code : '',
            $c->name,
            $c->position,
            $c->phone,
            $c->email,
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'PIC Name',
            'Position',
            'Phone',
            'Email',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments
                $sheet->getComment('A1')->getText()->createTextRun("Must match an existing Customer Code in the system.");

                // 2. Visual Cues (Mandatory Fields = Red & Bold)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:E1')->getFont()->setBold(true);
            },
        ];
    }
}
