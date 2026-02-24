<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesForecastTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            [
                'CUST-001',             // customer_code
                'PROD-SKU-001',        // product_sku
                now()->format('Y-m-01'), // period (First day of month)
                1000,                   // qty
                'Initial forecast for testing', // notes
            ],
            [
                'CUST-002',
                'PROD-SKU-002',
                now()->addMonth()->format('Y-m-01'),
                2500,
                'Seasonal peak expectation',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'customer_code',
            'product_sku',
            'period',
            'qty',
            'notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $spreadsheet = $sheet->getDelegate()->getParent();

                $sheet->getComment('A1')->getText()->createTextRun('Required: Customer Code (e.g., CUST-001)');
                $sheet->getComment('B1')->getText()->createTextRun('Required: Product SKU (e.g., PROD-SKU-001)');
                $sheet->getComment('C1')->getText()->createTextRun("Required: Periode forecast.\nGunakan tanggal hari pertama bulan, format YYYY-MM-DD (e.g., 2026-01-01).");
                $sheet->getComment('D1')->getText()->createTextRun('Required: Qty forecast. Harus angka ≥ 0.');
                $sheet->getComment('E1')->getText()->createTextRun('Optional: Catatan tambahan untuk baris forecast ini.');

                $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                $sheet->getStyle('E1')->getFont()->setBold(true);

                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Sales Forecast');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Sales Forecast > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Satu baris = satu kombinasi Customer + Produk + Periode.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'customer_code *');
                $instructionSheet->setCellValue('B10', 'Wajib. Kode customer, harus sama persis dengan master customer.');
                $instructionSheet->setCellValue('A11', 'product_sku *');
                $instructionSheet->setCellValue('B11', 'Wajib. Kode SKU produk, harus sama dengan master product.');
                $instructionSheet->setCellValue('A12', 'period *');
                $instructionSheet->setCellValue('B12', 'Wajib. Tanggal hari pertama bulan forecast (YYYY-MM-DD), contoh 2026-01-01.');
                $instructionSheet->setCellValue('A13', 'qty *');
                $instructionSheet->setCellValue('B13', 'Wajib. Qty forecast untuk periode tersebut. Harus angka ≥ 0.');
                $instructionSheet->setCellValue('A14', 'notes');
                $instructionSheet->setCellValue('B14', 'Opsional. Catatan tambahan (misal: promo, seasonal, dsb).');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
