<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductAliasTemplateExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            [
                'SKU-001',
                'PT. Customer A',
                'customer',
                'CUST-SKU-001',
                'Customer Product Name A'
            ],
            [
                'SKU-002',
                'CV. Supplier B',
                'supplier',
                'SUPP-SKU-002',
                'Supplier Product Name B'
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'product_sku',
            'partner_name',
            'partner_type',
            'alias_sku',
            'alias_name',
        ];
    }

    public function title(): string
    {
        return 'Product Aliases Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_BLACK]],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFC9DAF8'); // Light Blue

                $sheet->getStyle('B1:C1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF2CC'); // Light Yellow

                $sheet->getStyle('D1:E1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3'); // Light Green

                $sheet->getComment('A1')->getText()->createTextRun('Primary Key: Product SKU must exist in the system.');
                $sheet->getComment('B1')->getText()->createTextRun('Mandatory: Exact name of the Customer or Supplier as registered in Contacts.');
                $sheet->getComment('C1')->getText()->createTextRun('Mandatory: Fill with "customer" or "supplier" (lowercase).');
                $sheet->getComment('D1')->getText()->createTextRun('Optional: The SKU code used by the partner. Required if Alias Name is empty.');
                $sheet->getComment('E1')->getText()->createTextRun('Optional: The Product Name used by the partner. Required if Alias SKU is empty.');

                // Data Validation for partner_type (Column C)
                $validation = $sheet->getCell('C2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"customer,supplier"');
                $sheet->setDataValidation('C2:C1000', $validation);

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Partner Aliases');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Inventory > Products > Import Aliases.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama (jangan mengubah header).');
                $instructionSheet->setCellValue('A6', '3. Product SKU harus sudah terdaftar di master Products.');
                $instructionSheet->setCellValue('A7', '4. Partner Name harus sama persis dengan nama Customer atau Supplier yang terdaftar.');
                $instructionSheet->setCellValue('A8', '5. Simpan file sebagai .xlsx lalu upload kembali di form Import Partner Aliases.');

                $instructionSheet->setCellValue('A10', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A11', 'product_sku *');
                $instructionSheet->setCellValue('B11', 'Wajib. SKU produk internal. Sistem akan mencari produk berdasarkan SKU ini.');
                $instructionSheet->setCellValue('A12', 'partner_name *');
                $instructionSheet->setCellValue('B12', 'Wajib. Nama customer atau supplier, harus sama dengan master data.');
                $instructionSheet->setCellValue('A13', 'partner_type *');
                $instructionSheet->setCellValue('B13', 'Wajib. Isi dengan "customer" atau "supplier" (huruf kecil).');
                $instructionSheet->setCellValue('A14', 'alias_sku');
                $instructionSheet->setCellValue('B14', 'Opsional. SKU yang digunakan oleh partner. Jika alias_name kosong, kolom ini sebaiknya diisi.');
                $instructionSheet->setCellValue('A15', 'alias_name');
                $instructionSheet->setCellValue('B15', 'Opsional. Nama produk versi partner. Jika alias_sku kosong, kolom ini sebaiknya diisi.');
                $instructionSheet->setCellValue('A17', 'Catatan:');
                $instructionSheet->setCellValue('B17', 'Satu kombinasi Product + Partner hanya boleh punya satu baris alias. Import akan otomatis update jika kombinasi yang sama sudah ada.');

                $instructionSheet->getColumnDimension('A')->setWidth(35);
                $instructionSheet->getColumnDimension('B')->setWidth(110);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A10')->getFont()->setBold(true);
            },
        ];
    }
}
