<?php

namespace App\Exports\Purchasing;

use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class GoodsReceiptDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Only export draft receipts
        return GoodsReceipt::where('status', 'draft')
            ->with(['purchaseOrder', 'supplier', 'warehouse', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($receipt) {
                return $receipt->items->map(function ($item) use ($receipt) {
                    return [
                        $receipt->grn_number,
                        $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : '',
                        $receipt->supplier ? $receipt->supplier->code : '',
                        $receipt->warehouse ? $receipt->warehouse->name : '',
                        $receipt->delivery_note_number,
                        $receipt->purchaseOrder ? $receipt->purchaseOrder->po_number : '',
                        $item->product ? ($item->product->code ?? $item->product->sku) : '',
                        $item->qty_received,
                    ];
                });
            });
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

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

                $redColor = new Color(Color::COLOR_RED);
                $blueColor = new Color(Color::COLOR_BLUE);
                $sheet->getStyle('A1')->getFont()->setColor($blueColor); // Distinct GRN Number
                $sheet->getStyle('B1:E1')->getFont()->setColor($redColor);
                $sheet->getStyle('G1:H1')->getFont()->setColor($redColor); // Required fields

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Goods Receipts');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template dengan atau tanpa Data Existing.');
                $instructionSheet->setCellValue('A5', '2. Jika GRN Number terisi: Sistem akan melakukan OVERWRITE item-item di GRN tersebut bila statusnya masih DRAFT (membutuhkan validasi checkbox centang).');
                $instructionSheet->setCellValue('A6', '3. Jika GRN Number kosong: Sistem akan membuat GRN Baru. Baris dengan Supplier Code + Warehouse Name + Delivery Note Number yang sama akan digabung menjadi satu GRN.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            },
        ];
    }
}
