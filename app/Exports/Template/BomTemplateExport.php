<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BomTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'BOM-001',
                'Sample BOM A',
                'PROD-001',
                '1.0',
                100,
                'PCS',
                'RM-001',
                2.5,
                'KG',
                0,
                'draft',
            ],
            [
                'BOM-001',
                'Sample BOM A',
                'PROD-001',
                '1.0',
                100,
                'PCS',
                'RM-002',
                1.0,
                'PCS',
                5,
                'draft',
            ],
            [
                'BOM-002',
                'Sample BOM B',
                'PROD-002',
                '1.0',
                1,
                'SET',
                'RM-003',
                3.0,
                'PCS',
                0,
                'draft',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'BOM Code',
            'BOM Name',
            'Product Code',
            'Version',
            'Output Qty',
            'Output Unit',
            'Component Code',
            'Component Qty',
            'Component Unit',
            'Scrap %',
            'Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Required. Unique BOM code. One code per finished product version.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    'Required. Human readable BOM name. Used as recipe name.'
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Required. Finished product code (SKU). Must match existing Product Code that is manufactured.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. BOM version text, e.g. 1.0, 2.0. Same BOM Code + Version will be grouped.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Required. Output quantity produced by this BOM (e.g. 1, 100). Minimum 0.0001.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Optional. Output unit symbol (e.g. PCS, KG). Leave blank to use product default unit.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Required. Component material code (SKU). Must match existing Product Code.'
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    'Required. Quantity of component needed for the given Output Qty.'
                );
                $sheet->getComment('I1')->getText()->createTextRun(
                    'Optional. Component unit symbol. Leave blank to use component product default unit.'
                );
                $sheet->getComment('J1')->getText()->createTextRun(
                    'Optional. Scrap percentage for this component (0–100). Negative values will be set to 0.'
                );
                $sheet->getComment('K1')->getText()->createTextRun(
                    'Optional. BOM status: draft, active, or archived. Leave blank for draft.'
                );

                $red = new Color(Color::COLOR_RED);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('C1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('D1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('E1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('G1')->getFont()->setBold(true)->setColor($red);
                $sheet->getStyle('H1')->getFont()->setBold(true)->setColor($red);

                $sheet->getStyle('F1')->getFont()->setBold(true);
                $sheet->getStyle('I1')->getFont()->setBold(true);
                $sheet->getStyle('J1')->getFont()->setBold(true);
                $sheet->getStyle('K1')->getFont()->setBold(true);

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Bill of Materials');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Manufacturing > Bill of Materials > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama (jangan mengubah header).');
                $instructionSheet->setCellValue('A6', '3. Baris dengan kombinasi BOM Code + Version yang sama akan digabung menjadi satu BOM dengan banyak komponen.');
                $instructionSheet->setCellValue('A7', '4. Satu baris mewakili satu komponen dari suatu BOM.');
                $instructionSheet->setCellValue('A8', '5. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A10', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A11', 'BOM Code *');
                $instructionSheet->setCellValue('B11', 'Wajib. Kode unik BOM. Tidak boleh sama dengan BOM yang sudah ada di sistem.');
                $instructionSheet->setCellValue('A12', 'BOM Name *');
                $instructionSheet->setCellValue('B12', 'Wajib. Nama resep/struktur BOM yang mudah dibaca.');
                $instructionSheet->setCellValue('A13', 'Product Code *');
                $instructionSheet->setCellValue('B13', 'Wajib. Kode produk jadi (SKU) yang diproduksi. Harus sudah ada di master produk dan bertipe manufactured.');
                $instructionSheet->setCellValue('A14', 'Version *');
                $instructionSheet->setCellValue('B14', 'Wajib. Menandai versi BOM, misalnya 1.0, 2.0. Kombinasi BOM Code + Version digunakan untuk grouping.');
                $instructionSheet->setCellValue('A15', 'Output Qty *');
                $instructionSheet->setCellValue('B15', 'Wajib. Qty hasil produksi untuk BOM ini (misalnya 1 pcs, 100 pcs). Harus angka > 0.');
                $instructionSheet->setCellValue('A16', 'Output Unit');
                $instructionSheet->setCellValue('B16', 'Opsional. Satuan hasil (PCS, KG, dll). Jika kosong, sistem akan menggunakan unit default produk.');
                $instructionSheet->setCellValue('A17', 'Component Code *');
                $instructionSheet->setCellValue('B17', 'Wajib. Kode material/komponen (SKU). Harus sudah ada di master produk.');
                $instructionSheet->setCellValue('A18', 'Component Qty *');
                $instructionSheet->setCellValue('B18', 'Wajib. Qty komponen yang dibutuhkan per Output Qty. Harus angka > 0.');
                $instructionSheet->setCellValue('A19', 'Component Unit');
                $instructionSheet->setCellValue('B19', 'Opsional. Satuan komponen. Jika kosong, sistem menggunakan unit default produk komponen.');
                $instructionSheet->setCellValue('A20', 'Scrap %');
                $instructionSheet->setCellValue('B20', 'Opsional. Persentase scrap/waste untuk komponen ini (0–100). Nilai negatif akan diubah menjadi 0.');
                $instructionSheet->setCellValue('A21', 'Status');
                $instructionSheet->setCellValue('B21', 'Opsional. Status BOM: draft, active, atau archived. Jika kosong akan diset sebagai draft.');

                $instructionSheet->getColumnDimension('A')->setWidth(30);
                $instructionSheet->getColumnDimension('B')->setWidth(100);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A10')->getFont()->setBold(true);
            },
        ];
    }
}
