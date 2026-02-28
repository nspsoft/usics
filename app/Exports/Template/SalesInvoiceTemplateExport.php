<?php

namespace App\Exports\Template;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesInvoiceTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Pull real Sales Orders that can be invoiced
        $orders = SalesOrder::with(['items.product'])
            ->whereIn('status', ['confirmed', 'processing', 'partial', 'shipped', 'delivered'])
            ->orderBy('order_date', 'desc')
            ->take(3)
            ->get();

        $rows = collect();
        $today = now()->format('Y-m-d');
        $dueDate = now()->addDays(30)->format('Y-m-d');

        if ($orders->count() > 0) {
            foreach ($orders as $so) {
                $items = $so->items->take(2);
                foreach ($items as $item) {
                    $rows->push([
                        $so->so_number,
                        $today,
                        $dueDate,
                        $item->product?->sku ?? 'SKU-UNKNOWN',
                        $item->qty,
                        $item->unit_price,
                        $item->discount_percent ?? 0,
                        'Contoh invoice dari ' . $so->so_number,
                    ]);
                }
            }
        } else {
            // Fallback if no invoiceable SOs exist
            $rows->push(['SO/2026/02/0001', $today, $dueDate, 'SKU-001', 100, 50000, 0, 'Contoh - isi SO Number yang valid']);
            $rows->push(['SO/2026/02/0001', $today, $dueDate, 'SKU-002', 50, 25000, 5, '']);
            $rows->push(['SO/2026/02/0002', $today, $dueDate, 'SKU-001', 200, 50000, 0, 'Contoh SO berbeda = Invoice terpisah']);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Invoice Date',
            'Due Date',
            'Product Code',
            'Qty',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun(
                    "Wajib. SO Number yang akan dibuatkan invoice. Harus SO yang valid di sistem."
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Wajib. Tanggal invoice dalam format YYYY-MM-DD."
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    "Wajib. Tanggal jatuh tempo dalam format YYYY-MM-DD."
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    "Wajib. Kode SKU produk. Harus sama dengan master product."
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    "Wajib. Jumlah yang akan di-invoice. Harus lebih dari 0."
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    "Wajib. Harga satuan produk."
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    "Opsional. Persentase diskon (0-100)."
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    "Opsional. Catatan tambahan untuk invoice."
                );

                // Instruction Sheet
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Sales Invoices');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Sales Invoices > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Baris dengan SO Number yang sama akan dikelompokkan menjadi 1 Invoice.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'SO Number *');
                $instructionSheet->setCellValue('B10', 'Wajib. Nomor Sales Order. Baris dengan SO Number sama akan dijadikan 1 Invoice.');
                $instructionSheet->setCellValue('A11', 'Invoice Date *');
                $instructionSheet->setCellValue('B11', 'Wajib. Tanggal invoice dalam format YYYY-MM-DD. Diambil dari baris pertama per SO.');
                $instructionSheet->setCellValue('A12', 'Due Date *');
                $instructionSheet->setCellValue('B12', 'Wajib. Tanggal jatuh tempo pembayaran dalam format YYYY-MM-DD.');
                $instructionSheet->setCellValue('A13', 'Product Code *');
                $instructionSheet->setCellValue('B13', 'Wajib. SKU produk, harus sama dengan master product.');
                $instructionSheet->setCellValue('A14', 'Qty *');
                $instructionSheet->setCellValue('B14', 'Wajib. Jumlah yang di-invoice. Harus angka > 0.');
                $instructionSheet->setCellValue('A15', 'Unit Price *');
                $instructionSheet->setCellValue('B15', 'Wajib. Harga satuan produk dalam mata uang dasar.');
                $instructionSheet->setCellValue('A16', 'Discount %');
                $instructionSheet->setCellValue('B16', 'Opsional. Persentase diskon per item (0-100). Kosongkan jika tidak ada diskon.');
                $instructionSheet->setCellValue('A17', 'Notes');
                $instructionSheet->setCellValue('B17', 'Opsional. Catatan tambahan. Diambil dari baris pertama per SO sebagai catatan invoice.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
