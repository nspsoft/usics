<?php

namespace App\Exports\Template;

use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptTemplateExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    public function collection()
    {
        // Pull real POs that can be received
        $orders = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])
            ->whereIn('status', ['confirmed', 'approved', 'partial'])
            ->orderBy('order_date', 'desc')
            ->take(3)
            ->get();

        $rows = collect();
        $today = now()->format('Y-m-d');

        if ($orders->count() > 0) {
            foreach ($orders as $po) {
                $items = $po->items->take(2);
                foreach ($items as $item) {
                    $remaining = max(0, $item->qty - ($item->qty_received ?? 0));
                    if ($remaining <= 0) $remaining = $item->qty;

                    $rows->push([
                        '', // GRN Number
                        $today,
                        $po->supplier?->code ?? 'SUP-001',
                        $po->warehouse?->name ?? 'Main Warehouse',
                        'SJ-' . now()->format('Ymd') . '-SAMPLE',
                        $po->po_number,
                        $item->product?->code ?? $item->product?->sku ?? 'PROD-001',
                        $remaining,
                    ]);
                }
            }
        } else {
            // Fallback if no receivable POs
            $supplier = Supplier::first();
            $warehouse = Warehouse::first();
            $products = Product::take(2)->get();

            $supCode = $supplier?->code ?? 'SUP-001';
            $whName = $warehouse?->name ?? 'Main Warehouse';

            $rows->push([
                '', $today, $supCode, $whName, 'SJ-SAMPLE-001', '',
                $products->first()?->code ?? $products->first()?->sku ?? 'PROD-001',
                100,
            ]);
            $rows->push([
                '', $today, $supCode, $whName, 'SJ-SAMPLE-001', '',
                $products->last()?->code ?? $products->last()?->sku ?? 'PROD-002',
                50,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'Receipt Date (YYYY-MM-DD)',
            'Supplier Code',
            'Warehouse Name',
            'Delivery Note Number',
            'PO Number',
            'Product Code',
            'Qty Received',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Optional/Key. Jika terisi dan opsi Overwrite dicentang, maka file akan menimpa seluruh item di GRN ini (khusus draft). Jika dikosongkan, sistem akan menggenerate GRN Number baru.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    'Wajib. Tanggal penerimaan barang. Format YYYY-MM-DD atau tanggal Excel.'
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Wajib. Kode supplier. Harus sama dengan Supplier Code di master supplier.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Wajib. Nama gudang. Harus sama dengan Warehouse Name di master gudang.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Wajib. Nomor delivery note / surat jalan dari supplier. Jika GRN Number kosong, baris dengan Supplier + Warehouse + Delivery Note Number yang sama akan digabung menjadi satu Goods Receipt otomatis.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Opsional. Nomor Purchase Order terkait. Jika diisi, harus sama dengan PO Number di sistem.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Wajib. Kode produk yang diterima. Harus sama dengan Product Code di master produk.'
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    'Wajib. Qty yang diterima. Harus berupa angka.'
                );
                
                $blueColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('A1')->getFont()->setColor($blueColor);
                $sheet->getStyle('B1:E1')->getFont()->setColor($redColor);
                $sheet->getStyle('G1:H1')->getFont()->setColor($redColor);

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Goods Receipts');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Goods Receipts > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Bila GRN Number dikosongkan: Baris dengan Supplier Code + Warehouse Name + Delivery Note Number yang sama akan digabung menjadi satu Goods Receipt otomatis.');
                $instructionSheet->setCellValue('A7', '4. Bila GRN Number diisi dengan valid: Bila opsi Overwrite dicentang, sistem akan menimpa detail GRN Draft tersebut secara menyeluruh.');
                $instructionSheet->setCellValue('A8', '5. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A10', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A11', 'GRN Number');
                $instructionSheet->setCellValue('B11', 'Kunci Penimpaan ATAU Opsional. Nomor dari sistem lama. Jika dikosongkan, sistem generate otomatis.');
                $instructionSheet->setCellValue('A12', 'Receipt Date (YYYY-MM-DD) *');
                $instructionSheet->setCellValue('B12', 'Wajib. Tanggal penerimaan barang. Format YYYY-MM-DD atau tanggal Excel.');
                $instructionSheet->setCellValue('A13', 'Supplier Code *');
                $instructionSheet->setCellValue('B13', 'Wajib. Kode supplier sesuai master supplier.');
                $instructionSheet->setCellValue('A14', 'Warehouse Name *');
                $instructionSheet->setCellValue('B14', 'Wajib. Nama gudang sesuai master gudang.');
                $instructionSheet->setCellValue('A15', 'Delivery Note Number *');
                $instructionSheet->setCellValue('B15', 'Wajib. Nomor delivery note / surat jalan dari supplier. Digunakan untuk mengelompokkan baris menjadi satu Goods Receipt.');
                $instructionSheet->setCellValue('A16', 'PO Number');
                $instructionSheet->setCellValue('B16', 'Opsional. Nomor Purchase Order di sistem ini. Jika diisi, sistem akan mencoba menghubungkan Goods Receipt ke PO tersebut.');
                $instructionSheet->setCellValue('A17', 'Product Code *');
                $instructionSheet->setCellValue('B17', 'Wajib. Kode produk yang diterima, sesuai master produk.');
                $instructionSheet->setCellValue('A18', 'Qty Received *');
                $instructionSheet->setCellValue('B18', 'Wajib. Qty yang diterima. Harus angka ≥ 0.');

                $instructionSheet->getColumnDimension('A')->setWidth(30);
                $instructionSheet->getColumnDimension('B')->setWidth(90);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
