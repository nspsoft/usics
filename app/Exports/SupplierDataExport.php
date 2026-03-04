<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SupplierDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Supplier::orderBy('name', 'asc')->get()->map(function ($supplier) {
            return [
                $supplier->code,
                $supplier->name,
                $supplier->contact_person,
                $supplier->address,
                $supplier->city,
                $supplier->phone,
                $supplier->fax,
                $supplier->email,
                $supplier->tax_id,
                $supplier->npwp,
                $supplier->payment_terms,
                $supplier->payment_days,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Contact Person',
            'Address',
            'City',
            'Phone',
            'Fax',
            'Email',
            'Tax ID',
            'NPWP',
            'Payment Terms',
            'Payment Days',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Optional/Key. Biarkan angka Kode untuk mengupdate (Overwrite) Supplier lama jika fitur Overwrite dicentang. Kosongkan baris ini untuk generate Supplier Baru.'
                );
                $sheet->getComment('K1')->getText()->createTextRun("e.g., Net 30, Cash, COD");

                // Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: Code (A1), Name (B1), Payment Terms (K1), Payment Days (L1)
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('K1:L1')->getFont()->setBold(true)->setColor($redColor);
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:J1')->getFont()->setBold(true);

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Suppliers');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Data Existing:');
                $instructionSheet->setCellValue('A4', 'Jika Anda menggunakan opsi "Include Existing Data", maka tabel utama akan terisi seluruh Profil Supplier.');
                $instructionSheet->setCellValue('A5', 'Untuk UPDATE Supplier lama: Biarkan angka Code di kolom A sesuai aslinya, ubah data lainnya, lalu centang opsi "Overwrite Existing Data" saat Import.');
                $instructionSheet->setCellValue('A6', 'Untuk INSERT Supplier baru: Hapus/Kosongkan sel di kolom A (Code) pada baris baru, maka sistem akan langsung men-generate kode unik secara acak.');

                $instructionSheet->getColumnDimension('A')->setWidth(60);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            },
        ];
    }
}
