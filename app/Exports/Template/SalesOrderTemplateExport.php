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
    protected bool $withData;

    public function __construct(bool $withData = false)
    {
        $this->withData = $withData;
    }

    public function collection()
    {
        $customers = Customer::active()->orderBy('name')->take(2)->get();
        $products = Product::with('unit')->orderBy('name')->take(3)->get();
        $warehouse = Warehouse::first();

        $custCode1 = $customers->first()?->code ?? 'CUST-001';
        $custCode2 = ($customers->count() > 1 ? $customers->last()?->code : $customers->first()?->code) ?? 'CUST-002';
        $whCode = $warehouse?->code ?? 'WH-MAIN';
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $nextWeek = now()->addWeek()->format('Y-m-d');

        $rows = collect();

        if ($products->count() >= 2) {
            $p1 = $products[0];
            $p2 = $products[1];
            $p3 = $products->count() > 2 ? $products[2] : $products[0];

            // Row 1 & 2: Same customer for CREATE mode (no SO Number)
            $rows->push([
                '',  // SO Number (kosongkan untuk buat baru)
                $custCode1,
                'PO-SAMPLE-001',
                $whCode,
                $today,
                $nextWeek,
                $p1->sku,
                100,
                $p1->unit?->code ?? $p1->unit?->name ?? 'PCS',
                $p1->selling_price ?: 10000,
                0,
                'Contoh order baru (SO Number kosong = buat SO baru)',
            ]);
            $rows->push([
                '',  // SO Number
                $custCode1,
                'PO-SAMPLE-001',
                $whCode,
                $today,
                $nextWeek,
                $p2->sku,
                50,
                $p2->unit?->code ?? $p2->unit?->name ?? 'PCS',
                $p2->selling_price ?: 15000,
                5,
                '',
            ]);
            // Row 3: Example for UPDATE mode (with SO Number)
            $rows->push([
                'SO/26/01/001',  // SO Number (isi untuk update SO yang ada)
                $custCode2,
                '',
                $whCode,
                $tomorrow,
                $nextWeek,
                $p3->sku,
                200,
                $p3->unit?->code ?? $p3->unit?->name ?? 'PCS',
                $p3->selling_price ?: 20000,
                0,
                'Contoh UPDATE: isi SO Number untuk update harga/qty/tanggal order yang sudah ada',
            ]);
        } else {
            $p = $products->first();
            $sku = $p?->sku ?? 'PROD-001';
            $unit = $p?->unit?->code ?? 'PCS';
            $price = $p?->selling_price ?: 10000;

            $rows->push(['', $custCode1, 'PO-SAMPLE-001', $whCode, $today, $nextWeek, $sku, 100, $unit, $price, 0, 'Contoh order baru']);
            $rows->push(['SO/26/01/001', $custCode2, '', $whCode, $tomorrow, $nextWeek, $sku, 200, $unit, $price, 0, 'Contoh update SO']);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Customer Code',
            'Customer PO',
            'Warehouse Code',
            'Order Date',
            'Delivery Date',
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

                // Comments
                $sheet->getComment('A1')->getText()->createTextRun("Opsional. Isi dengan SO Number yang ada untuk MODE UPDATE.\nKosongkan untuk membuat SO baru (MODE CREATE).\nMode UPDATE bisa mengubah harga, qty, tanggal pada order yang sudah Confirmed.");
                $sheet->getComment('B1')->getText()->createTextRun("Wajib. Harus sesuai dengan Customer Code yang ada di master.");
                $sheet->getComment('C1')->getText()->createTextRun("Opsional. Nomor PO dari customer.");
                $sheet->getComment('D1')->getText()->createTextRun("Wajib. Harus sesuai dengan Warehouse Code yang ada di master.");
                $sheet->getComment('E1')->getText()->createTextRun("Wajib. Format: YYYY-MM-DD\nBaris dengan Customer Code + Customer PO + Order Date yang sama akan dikelompokkan menjadi 1 SO.");
                $sheet->getComment('F1')->getText()->createTextRun("Opsional. Tanggal pengiriman. Format: YYYY-MM-DD");
                $sheet->getComment('G1')->getText()->createTextRun("Wajib. Harus sesuai dengan SKU produk di master.");
                $sheet->getComment('H1')->getText()->createTextRun("Wajib. Minimal: 0.0001");
                $sheet->getComment('I1')->getText()->createTextRun("Opsional. Harus sesuai unit code. Kosongkan untuk unit default produk.");
                $sheet->getComment('J1')->getText()->createTextRun("Wajib. Harga satuan dalam mata uang dasar.");

                // SO Number column in blue (special)
                $blueColor = new \PhpOffice\PhpSpreadsheet\Style\Color('3B82F6');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setColor($blueColor);

                // Mandatory fields in red bold
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('D1:E1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('J1')->getFont()->setBold(true)->setColor($redColor);

                // Optional fields bold
                $sheet->getStyle('C1')->getFont()->setBold(true);
                $sheet->getStyle('F1')->getFont()->setBold(true);
                $sheet->getStyle('I1')->getFont()->setBold(true);
                $sheet->getStyle('K1:L1')->getFont()->setBold(true);

                // Instruction Sheet
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Sales Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', '📌 DUA MODE IMPORT:');
                $instructionSheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);

                $instructionSheet->setCellValue('A5', 'MODE 1: CREATE (Buat SO Baru)');
                $instructionSheet->getStyle('A5')->getFont()->setBold(true);
                $instructionSheet->setCellValue('A6', '- Kosongkan kolom "SO Number".');
                $instructionSheet->setCellValue('A7', '- Baris dengan Customer Code + Customer PO + Order Date sama akan dikelompokkan menjadi 1 SO.');
                $instructionSheet->setCellValue('A8', '- SO baru akan dibuat dengan status Draft.');

                $instructionSheet->setCellValue('A10', 'MODE 2: UPDATE (Revisi Order yang Sudah Ada)');
                $instructionSheet->getStyle('A10')->getFont()->setBold(true);
                $instructionSheet->setCellValue('A11', '- Isi kolom "SO Number" dengan nomor SO yang ingin diupdate.');
                $instructionSheet->setCellValue('A12', '- Bisa mengubah: Unit Price, Qty, Order Date, Delivery Date, Customer PO, Notes.');
                $instructionSheet->setCellValue('A13', '- Berlaku untuk SEMUA status (Draft, Confirmed, Processing, dll) kecuali Cancelled.');
                $instructionSheet->setCellValue('A14', '- Item dicocokkan berdasarkan Product Code/SKU. Jika product belum ada di SO, akan ditambahkan.');

                $instructionSheet->setCellValue('A16', 'Langkah umum:');
                $instructionSheet->getStyle('A16')->getFont()->setBold(true);
                $instructionSheet->setCellValue('A17', '1. Download template ini atau Export data yang ada.');
                $instructionSheet->setCellValue('A18', '2. Edit data sesuai kebutuhan di sheet utama.');
                $instructionSheet->setCellValue('A19', '3. Simpan file sebagai .xlsx lalu upload di form Import.');

                $instructionSheet->setCellValue('A21', 'Keterangan kolom:');
                $instructionSheet->getStyle('A21')->getFont()->setBold(true);
                $instructionSheet->setCellValue('A22', 'SO Number');
                $instructionSheet->setCellValue('B22', 'Opsional. Isi untuk update SO yang ada. Kosongkan untuk buat baru.');
                $instructionSheet->setCellValue('A23', 'Customer Code *');
                $instructionSheet->setCellValue('B23', 'Wajib. Kode customer sesuai master. Contoh: CUST-0001');
                $instructionSheet->setCellValue('A24', 'Customer PO');
                $instructionSheet->setCellValue('B24', 'Opsional. Nomor PO dari customer.');
                $instructionSheet->setCellValue('A25', 'Warehouse Code *');
                $instructionSheet->setCellValue('B25', 'Wajib. Kode atau nama gudang sesuai master.');
                $instructionSheet->setCellValue('A26', 'Order Date *');
                $instructionSheet->setCellValue('B26', 'Wajib. Format YYYY-MM-DD.');
                $instructionSheet->setCellValue('A27', 'Delivery Date');
                $instructionSheet->setCellValue('B27', 'Opsional. Tanggal pengiriman, format YYYY-MM-DD.');
                $instructionSheet->setCellValue('A28', 'Product Code *');
                $instructionSheet->setCellValue('B28', 'Wajib. SKU produk sesuai master product.');
                $instructionSheet->setCellValue('A29', 'Qty *');
                $instructionSheet->setCellValue('B29', 'Wajib. Jumlah order. Harus angka > 0.');
                $instructionSheet->setCellValue('A30', 'Unit Code');
                $instructionSheet->setCellValue('B30', 'Opsional. Kode unit. Kosongkan untuk menggunakan unit default produk.');
                $instructionSheet->setCellValue('A31', 'Unit Price *');
                $instructionSheet->setCellValue('B31', 'Wajib. Harga satuan produk.');
                $instructionSheet->setCellValue('A32', 'Discount %');
                $instructionSheet->setCellValue('B32', 'Opsional. Persentase diskon per item (0-100).');
                $instructionSheet->setCellValue('A33', 'Notes');
                $instructionSheet->setCellValue('B33', 'Opsional. Catatan tambahan.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
            },
        ];
    }
}
