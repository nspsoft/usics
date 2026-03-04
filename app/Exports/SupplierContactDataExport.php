<?php

namespace App\Exports;

use App\Models\SupplierContact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SupplierContactDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return SupplierContact::with('supplier')->orderBy('supplier_id')->get()->map(function ($contact) {
            return [
                $contact->supplier ? $contact->supplier->code : '',
                $contact->name,
                $contact->position,
                $contact->phone,
                $contact->email,
                $contact->id, // Add ID for accurate overwrite mapping
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Supplier Code',
            'PIC Name',
            'Position',
            'Phone',
            'Email',
            'Internal ID (Do Not Change)',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun("Must match an existing Supplier Code in the system.");
                $sheet->getComment('F1')->getText()->createTextRun("Sistem mengunakan ID ini untuk menimpa kontak yang tepat. Dilarang mengubah angka ini jika Overwrite dicentang.");

                // Mandatory: Supplier Code (A1), PIC Name (B1)
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $grayColor = new \PhpOffice\PhpSpreadsheet\Style\Color('FF808080'); // Gray for Internal ID

                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('C1:E1')->getFont()->setBold(true);
                $sheet->getStyle('F1')->getFont()->setBold(true)->setColor($grayColor);

                // Set column F narrower since it's an internal ID
                $sheet->getColumnDimension('F')->setWidth(25);

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Kontak Supplier');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Data Existing / Overwrite:');
                $instructionSheet->setCellValue('A4', 'Jika Anda menggunakan opsi "Include Existing Data", setiap nomor kontak akan memiliki kolom tambahan [Internal ID] yang mendampinginya.');
                $instructionSheet->setCellValue('A5', 'Untuk UPDATE Kontak lama: Pastikan Anda centang opsi "Overwrite Existing Data". Sistem akan mencari kontak berdasarkan kombinasi Internal ID.');
                $instructionSheet->setCellValue('A6', 'Untuk INSERT/CREATE Kontak baru: Biarkan sel Internal ID KOSONG, sistem akan menambahkannya sebagai kontak baru untuk Supplier Code tersebut.');

                $instructionSheet->getColumnDimension('A')->setWidth(75);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            },
        ];
    }
}
