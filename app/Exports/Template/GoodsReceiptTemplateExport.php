<?php

namespace App\Exports\Template;

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
        // Return a single example row
        return collect([
            [
                '2023-10-25', // Receipt Date
                'SUP-001',    // Supplier Code
                'WH-MAIN',    // Warehouse Name
                'DN-12345',   // Delivery Note
                'PO-2023-001',// PO Number
                'PROD-001',   // Product Code
                '100',        // Qty Received
            ]
        ]);
    }

    public function headings(): array
    {
        return [
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
                    'Required. Tanggal penerimaan barang. Format YYYY-MM-DD atau tanggal Excel.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    'Required. Kode supplier. Harus sama dengan Supplier Code di master supplier.'
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Required. Nama gudang. Harus sama dengan Warehouse Name di master gudang.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. Nomor delivery note / surat jalan dari supplier. Baris dengan Supplier + Warehouse + Delivery Note Number yang sama akan digabung menjadi satu Goods Receipt.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Optional. Nomor Purchase Order terkait. Jika diisi, harus sama dengan PO Number di sistem.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Required. Kode produk yang diterima. Harus sama dengan Product Code di master produk.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Required. Qty yang diterima. Harus berupa angka.'
                );

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Goods Receipts');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Goods Receipts > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Baris dengan Supplier Code + Warehouse Name + Delivery Note Number yang sama akan digabung menjadi satu Goods Receipt.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'Receipt Date (YYYY-MM-DD) *');
                $instructionSheet->setCellValue('B10', 'Wajib. Tanggal penerimaan barang. Format YYYY-MM-DD atau tanggal Excel.');
                $instructionSheet->setCellValue('A11', 'Supplier Code *');
                $instructionSheet->setCellValue('B11', 'Wajib. Kode supplier sesuai master supplier.');
                $instructionSheet->setCellValue('A12', 'Warehouse Name *');
                $instructionSheet->setCellValue('B12', 'Wajib. Nama gudang sesuai master gudang.');
                $instructionSheet->setCellValue('A13', 'Delivery Note Number *');
                $instructionSheet->setCellValue('B13', 'Wajib. Nomor delivery note / surat jalan dari supplier. Digunakan untuk mengelompokkan baris menjadi satu Goods Receipt.');
                $instructionSheet->setCellValue('A14', 'PO Number');
                $instructionSheet->setCellValue('B14', 'Opsional. Nomor Purchase Order di sistem ini. Jika diisi, sistem akan mencoba menghubungkan Goods Receipt ke PO tersebut.');
                $instructionSheet->setCellValue('A15', 'Product Code *');
                $instructionSheet->setCellValue('B15', 'Wajib. Kode produk yang diterima, sesuai master produk.');
                $instructionSheet->setCellValue('A16', 'Qty Received *');
                $instructionSheet->setCellValue('B16', 'Wajib. Qty yang diterima. Harus angka ≥ 0.');

                $instructionSheet->getColumnDimension('A')->setWidth(30);
                $instructionSheet->getColumnDimension('B')->setWidth(90);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
