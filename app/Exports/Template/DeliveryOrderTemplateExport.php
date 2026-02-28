<?php

namespace App\Exports\Template;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DeliveryOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Pull real Sales Orders that can be delivered
        $orders = SalesOrder::with(['items.product', 'items.unit'])
            ->whereIn('status', ['confirmed', 'processing', 'partial'])
            ->orderBy('order_date', 'desc')
            ->take(3)
            ->get();

        $rows = collect();
        $today = now()->format('Y-m-d');

        if ($orders->count() > 0) {
            foreach ($orders as $so) {
                $items = $so->items->take(2); // max 2 items per SO for sample
                foreach ($items as $item) {
                    $remaining = max(0, $item->qty - ($item->qty_delivered ?? 0));
                    if ($remaining <= 0) $remaining = $item->qty; // show original qty if fully delivered

                    $rows->push([
                        '',                                         // DO Number (auto-generate)
                        $so->so_number,                              // SO Number
                        $so->customer_po_number ?? '',               // Customer PO
                        $item->product?->sku ?? 'SKU-UNKNOWN',      // Product Code
                        $remaining,                                  // Qty Delivered
                        $today,                                      // Delivery Date
                        '',                                          // Batch Number
                        'Contoh: sisa qty dari ' . $so->so_number,   // Notes
                    ]);
                }
            }
        } else {
            // Fallback if no deliverable SOs exist
            $rows->push(['', 'SO/2026/02/0001', 'PO-CUST-001', 'SKU-001', 100, $today, '', 'Contoh - isi SO Number yang sudah Confirmed']);
            $rows->push(['', 'SO/2026/02/0001', 'PO-CUST-001', 'SKU-002', 50, $today, 'BATCH-001', '']);
            $rows->push(['DO-CUSTOM-001', 'SO/2026/02/0002', '', 'SKU-001', 200, $today, '', 'Contoh dengan DO Number custom']);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'DO Number',
            'SO Number',
            'Customer PO Number (Reference only)',
            'Product Code',
            'Qty Delivered',
            'Delivery Date (YYYY-MM-DD)',
            'Batch Number',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Optional. Nomor DO sesuai sistem lama. Jika diisi akan dipakai sebagai DO Number.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    'Required. SO Number untuk DO ini. Harus SO yang statusnya confirmed/processing/partial.'
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Optional. Nomor PO Customer hanya untuk referensi. Tidak mempengaruhi proses import.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. Kode SKU produk. Harus sama dengan master product.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Required. Qty yang akan dikirim. Dianjurkan lebih dari 0.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Required. Tanggal delivery DO dalam format YYYY-MM-DD atau tanggal Excel.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Optional. Nomor batch atau lot untuk keperluan traceability.'
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    'Optional. Catatan tambahan untuk baris DO ini.'
                );

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Delivery Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Delivery Orders > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Satu baris = satu item DO untuk 1 SO.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'DO Number');
                $instructionSheet->setCellValue('B10', 'Opsional. Nomor DO dari sistem lama. Jika dikosongkan, sistem akan membuat nomor DO otomatis.');
                $instructionSheet->setCellValue('A11', 'SO Number *');
                $instructionSheet->setCellValue('B11', 'Wajib. Nomor Sales Order. Harus ada di sistem dan status boleh dikirim.');
                $instructionSheet->setCellValue('A12', 'Customer PO Number');
                $instructionSheet->setCellValue('B12', 'Opsional. Nomor PO dari customer, hanya untuk referensi dan pengecekan manual.');
                $instructionSheet->setCellValue('A13', 'Product Code *');
                $instructionSheet->setCellValue('B13', 'Wajib. SKU produk, harus sama dengan master product pada SO tersebut.');
                $instructionSheet->setCellValue('A14', 'Qty Delivered *');
                $instructionSheet->setCellValue('B14', 'Wajib. Qty yang dikirim untuk item tersebut. Harus angka ≥ 0.');
                $instructionSheet->setCellValue('A15', 'Delivery Date *');
                $instructionSheet->setCellValue('B15', 'Wajib. Tanggal DO. Format YYYY-MM-DD atau tanggal Excel (diasumsikan waktu lokal).');
                $instructionSheet->setCellValue('A16', 'Batch Number');
                $instructionSheet->setCellValue('B16', 'Opsional. Nomor batch/lot jika Anda menggunakan tracking batch.');
                $instructionSheet->setCellValue('A17', 'Notes');
                $instructionSheet->setCellValue('B17', 'Opsional. Catatan tambahan untuk baris DO.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
