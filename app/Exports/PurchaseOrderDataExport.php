<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PurchaseOrderDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Only export draft requests, as others shouldn't be overridden typically
        return PurchaseOrder::where('status', 'draft')
            ->with(['supplier', 'warehouse', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($po) {
                return $po->items->map(function ($item) use ($po) {
                    return [
                        $po->po_number,
                        $po->order_date ? \Carbon\Carbon::parse($po->order_date)->format('Y-m-d') : '',
                        $po->expected_date ? \Carbon\Carbon::parse($po->expected_date)->format('Y-m-d') : '',
                        $po->supplier ? $po->supplier->code : '',
                        $po->warehouse ? $po->warehouse->name : '',
                        $item->product ? ($item->product->code ?? $item->product->sku) : '',
                        $item->qty,
                        $item->unit_price,
                        $item->discount_percent,
                        $item->notes,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Expected Date',
            'Supplier Code',
            'Warehouse Name',
            'Product Code',
            'Quantity',
            'Unit Price',
            'Discount %',
            'Notes',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Optional/Key. Jika terisi dan opsi Overwrite dicentang, maka file akan menimpa seluruh item di PO Number ini (khusus draft). Jika dikosongkan, sistem akan menggenerate PO Number baru.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Required. Tanggal PO dalam format YYYY-MM-DD atau tanggal Excel.\nBaris dengan Order Date + Supplier + Warehouse yang sama akan digabung menjadi satu PO jika PO Number kosong."
                );
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Optional. Tanggal estimasi kedatangan barang. Format YYYY-MM-DD atau tanggal Excel.'
                );
                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. Kode supplier. Harus sama persis dengan Supplier Code di master supplier.'
                );
                $sheet->getComment('E1')->getText()->createTextRun(
                    'Required. Nama gudang. Harus sama persis dengan Warehouse Name di master gudang.'
                );
                $sheet->getComment('F1')->getText()->createTextRun(
                    'Required. Kode produk. Harus sama dengan Product Code di master produk.'
                );
                $sheet->getComment('G1')->getText()->createTextRun(
                    'Required. Qty yang dipesan. Harus berupa angka.'
                );
                $sheet->getComment('H1')->getText()->createTextRun(
                    'Required. Harga per unit. Harus berupa angka.'
                );
                $sheet->getComment('I1')->getText()->createTextRun(
                    'Optional. Diskon dalam persen (0-100).'
                );
                $sheet->getComment('J1')->getText()->createTextRun(
                    'Optional. Catatan tambahan untuk baris PO ini.'
                );

                $redColor = new Color(Color::COLOR_RED);
                $blueColor = new Color(Color::COLOR_BLUE);
                $sheet->getStyle('A1')->getFont()->setColor($blueColor); // Distinct PO Number
                $sheet->getStyle('B1')->getFont()->setColor($redColor);
                $sheet->getStyle('D1:H1')->getFont()->setColor($redColor); // Required fields

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Purchase Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template dengan atau tanpa Data Existing.');
                $instructionSheet->setCellValue('A5', '2. Jika PO Number terisi: Sistem akan melakukan OVERWRITE item-item di PO tersebut bila statusnya masih DRAFT (membutuhkan validasi checkbox centang).');
                $instructionSheet->setCellValue('A6', '3. Jika PO Number kosong: Sistem akan membuat PO Baru. Baris dengan Order Date + Supplier Code + Warehouse Name yang sama akan digabung menjadi satu PO.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            },
        ];
    }
}
