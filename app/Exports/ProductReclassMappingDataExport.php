<?php

namespace App\Exports;

use App\Models\Inventory\ProductReclassMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ProductReclassMappingDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return ProductReclassMapping::query()
            ->with(['sourceProduct:id,sku', 'targetProduct:id,sku'])
            ->orderBy('source_product_id')
            ->get()
            ->map(function ($m) {
                return [
                    $m->sourceProduct ? $m->sourceProduct->sku : '',
                    $m->targetProduct ? $m->targetProduct->sku : '',
                    $m->is_active ? 'Yes' : 'No',
                    $m->is_default ? 'Yes' : 'No',
                    $m->notes,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Source Product SKU',
            'Target Product SKU',
            'Is Active',
            'Is Default',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments
                $sheet->getComment('C1')->getText()->createTextRun("Pilihan:\n- Yes\n- No\n\nDefault: Yes");
                $sheet->getComment('D1')->getText()->createTextRun("Pilihan:\n- Yes\n- No\n\nDefault: No. Wajib ada 1 default target per Source.");

                // 2. Data Validation (Dropdowns)
                $validation = $sheet->getCell('C2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"Yes,No"');
                
                $sheet->setDataValidation('C2:C1000', $validation); // Is Active
                $sheet->setDataValidation('D2:D1000', $validation); // Is Default

                // 3. Visual Cues (Mandatory Fields = Red & Bold)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
                $sheet->getStyle('C1:E1')->getFont()->setBold(true);

                // 4. Instruction Sheet
                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Reclass Mappings');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Inventory > Reclass Mapping > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama (jangan mengubah header).');
                $instructionSheet->setCellValue('A6', '3. Source Product SKU dan Target Product SKU harus merupakan SKU produk aktif yang sudah terdaftar di master produk.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import Reclass Mappings.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'Source Product SKU *');
                $instructionSheet->setCellValue('B10', 'Wajib. SKU produk asal yang akan di-reclass.');
                $instructionSheet->setCellValue('A11', 'Target Product SKU *');
                $instructionSheet->setCellValue('B11', 'Wajib. SKU produk tujuan hasil reclass. Harus berbeda dari Source SKU.');
                $instructionSheet->setCellValue('A12', 'Is Active');
                $instructionSheet->setCellValue('B12', 'Opsional. Pilihan: Yes atau No. Default: Yes.');
                $instructionSheet->setCellValue('A13', 'Is Default');
                $instructionSheet->setCellValue('B13', 'Opsional. Pilihan: Yes atau No. Default: No. Jika Source memiliki lebih dari satu Target, salah satu wajib diset Default = Yes.');
                $instructionSheet->setCellValue('A14', 'Notes');
                $instructionSheet->setCellValue('B14', 'Opsional. Catatan tambahan untuk mapping ini.');

                $instructionSheet->getColumnDimension('A')->setWidth(40);
                $instructionSheet->getColumnDimension('B')->setWidth(110);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            }
        ];
    }
}
