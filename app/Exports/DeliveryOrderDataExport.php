<?php

namespace App\Exports;

use App\Models\DeliveryOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DeliveryOrderDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return DeliveryOrder::with(['salesOrder', 'items.product', 'items.unit', 'items.salesOrderItem'])
            ->orderBy('delivery_date', 'desc')
            ->get()
            ->flatMap(function ($do) {
                if ($do->items->isEmpty()) {
                    return [];
                }
                return $do->items->map(function ($item) use ($do) {
                    return [
                        $do->do_number,
                        $do->salesOrder ? $do->salesOrder->so_number : '',
                        $do->salesOrder ? $do->salesOrder->customer_po_number : '',
                        $item->product ? $item->product->sku : '',
                        $item->qty_delivered,
                        $do->delivery_date ? $do->delivery_date->format('Y-m-d') : '',
                        $item->batch_number,
                        $item->notes,
                    ];
                });
            });
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
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Optional / Key. Biarkan ada untuk mengupdate (Overwrite) DO lama. Kosongkan untuk membuat DO baru.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    'Required. SO Number untuk referensi DO ini.'
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Optional. Nomor PO Customer hanya untuk referensi. Tidak mempengaruhi proses import.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. Kode SKU produk. Harus sama dengan master product.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Required. Qty yang akan dikirim (atau diperbarui jika overwrite). Dianjurkan lebih dari 0.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Required. Tanggal delivery DO format YYYY-MM-DD.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Optional. Nomor batch atau lot.'
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    'Optional. Catatan pengiriman.'
                );

                $redColor = new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->getStyle('B1')->getFont()->setBold(true)->setColor($redColor);
                $sheet->getStyle('D1:F1')->getFont()->setBold(true)->setColor($redColor);
                
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('C1')->getFont()->setBold(true);
                $sheet->getStyle('G1:H1')->getFont()->setBold(true);

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Delivery Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Data Existing:');
                $instructionSheet->setCellValue('A4', 'Jika Anda menggunakan opsi "Include Existing Data", maka kolom DO Number akan terisi.');
                $instructionSheet->setCellValue('A5', 'Untuk UPDATE baris DO lama: Biarkan angka DO Number di kolom A, lalu centang "Overwrite Existing Data" saat Import.');
                $instructionSheet->setCellValue('A6', 'Untuk INSERT/CREATE DO baru: Kosongkan sel di kolom A (DO Number), maka sistem akan membuat DO otomatis berdasarkan SO Number.');

                $instructionSheet->getColumnDimension('A')->setWidth(60);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            },
        ];
    }
}
