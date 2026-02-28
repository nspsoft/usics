<?php

namespace App\Exports\Template;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Pull real data from database for sample rows
        $customers = Customer::active()->orderBy('name')->take(2)->get();
        $products = Product::with('unit')->orderBy('name')->take(3)->get();
        $warehouse = Warehouse::first();

        $custCode1 = $customers->first()?->code ?? 'CUST-001';
        $custCode2 = ($customers->count() > 1 ? $customers->last()?->code : $customers->first()?->code) ?? 'CUST-002';
        $whCode = $warehouse?->code ?? 'WH-MAIN';
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $rows = collect();

        if ($products->count() >= 2) {
            $p1 = $products[0];
            $p2 = $products[1];
            $p3 = $products->count() > 2 ? $products[2] : $products[0];

            // Row 1 & 2: Same customer + same date = grouped into one SO
            $rows->push([
                $custCode1,
                'PO-SAMPLE-001',
                $whCode,
                $today,
                $p1->sku,
                100,
                $p1->unit?->code ?? $p1->unit?->name ?? 'PCS',
                $p1->selling_price ?: 10000,
                0,
                'Contoh order (baris ini & baris berikutnya akan jadi 1 SO karena Customer + PO + Date sama)',
            ]);
            $rows->push([
                $custCode1,
                'PO-SAMPLE-001',
                $whCode,
                $today,
                $p2->sku,
                50,
                $p2->unit?->code ?? $p2->unit?->name ?? 'PCS',
                $p2->selling_price ?: 15000,
                5,
                '',
            ]);
            // Row 3: Different customer = new SO
            $rows->push([
                $custCode2,
                '',
                $whCode,
                $tomorrow,
                $p3->sku,
                200,
                $p3->unit?->code ?? $p3->unit?->name ?? 'PCS',
                $p3->selling_price ?: 20000,
                0,
                'Contoh SO terpisah (customer berbeda)',
            ]);
        } else {
            // Fallback: minimal data
            $p = $products->first();
            $sku = $p?->sku ?? 'PROD-001';
            $unit = $p?->unit?->code ?? 'PCS';
            $price = $p?->selling_price ?: 10000;

            $rows->push([$custCode1, 'PO-SAMPLE-001', $whCode, $today, $sku, 100, $unit, $price, 0, 'Contoh order']);
            $rows->push([$custCode2, '', $whCode, $tomorrow, $sku, 200, $unit, $price, 0, 'Contoh SO lain']);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer PO',
            'Warehouse Code',
            'Order Date',
            'Product Code',
            'Qty',
            'Unit Code',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $spreadsheet = $sheet->getDelegate()->getParent();

                // Instructions via comments (Indonesian)
                $sheet->getComment('A1')->getText()->createTextRun("Wajib. Harus sesuai dengan Customer Code yang ada di master.");
                $sheet->getComment('B1')->getText()->createTextRun("Opsional. Nomor PO dari customer.");
                $sheet->getComment('C1')->getText()->createTextRun("Wajib. Harus sesuai dengan Warehouse Code yang ada di master.");
                $sheet->getComment('D1')->getText()->createTextRun("Wajib. Format: YYYY-MM-DD\nBaris dengan Customer Code + Customer PO + Order Date yang sama akan dikelompokkan menjadi 1 SO.");
                $sheet->getComment('E1')->getText()->createTextRun("Wajib. Harus sesuai dengan SKU produk di master.");
                $sheet->getComment('F1')->getText()->createTextRun("Wajib. Minimal: 0.0001");
                $sheet->getComment('G1')->getText()->createTextRun("Opsional. Harus sesuai unit code. Kosongkan untuk unit default produk.");
                $sheet->getComment('H1')->getText()->createTextRun("Wajib. Harga satuan dalam mata uang dasar.");

                // Mandatory fields in red bold
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('C1:F1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('H1')->getFont()->setBold(true)->setColor($redColor);

                // Optional fields in standard bold
                $sheet->getStyle('B1')->getFont()->setBold(true);
                $sheet->getStyle('G1')->getFont()->setBold(true);
                $sheet->getStyle('I1:J1')->getFont()->setBold(true);

                // Instruction Sheet
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Sales Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Sales Orders > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Baris dengan Customer Code + Customer PO + Order Date sama akan dikelompokkan menjadi 1 SO.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'Customer Code *');
                $instructionSheet->setCellValue('B10', 'Wajib. Kode customer sesuai master. Contoh: CUST-0001');
                $instructionSheet->setCellValue('A11', 'Customer PO');
                $instructionSheet->setCellValue('B11', 'Opsional. Nomor PO dari customer. Ikut menentukan pengelompokan SO.');
                $instructionSheet->setCellValue('A12', 'Warehouse Code *');
                $instructionSheet->setCellValue('B12', 'Wajib. Kode atau nama gudang sesuai master.');
                $instructionSheet->setCellValue('A13', 'Order Date *');
                $instructionSheet->setCellValue('B13', 'Wajib. Tanggal order format YYYY-MM-DD. Ikut menentukan pengelompokan SO.');
                $instructionSheet->setCellValue('A14', 'Product Code *');
                $instructionSheet->setCellValue('B14', 'Wajib. SKU produk sesuai master product.');
                $instructionSheet->setCellValue('A15', 'Qty *');
                $instructionSheet->setCellValue('B15', 'Wajib. Jumlah order. Harus angka > 0.');
                $instructionSheet->setCellValue('A16', 'Unit Code');
                $instructionSheet->setCellValue('B16', 'Opsional. Kode unit. Kosongkan untuk menggunakan unit default produk.');
                $instructionSheet->setCellValue('A17', 'Unit Price *');
                $instructionSheet->setCellValue('B17', 'Wajib. Harga satuan produk dalam mata uang dasar.');
                $instructionSheet->setCellValue('A18', 'Discount %');
                $instructionSheet->setCellValue('B18', 'Opsional. Persentase diskon per item (0-100).');
                $instructionSheet->setCellValue('A19', 'Notes');
                $instructionSheet->setCellValue('B19', 'Opsional. Catatan tambahan. Diambil dari baris pertama per grup SO.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
