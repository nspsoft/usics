<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryScheduleTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'CUST-0001',
                'PT. Contoh Customer',
                'PO-2024-001',
                'PROD-001',
                'Pipa Galvanis 2 inch',
                '100',
                'REF-001',
                'Arrived at Port',
                '2024-03-01',
            ],
            [
                'CUST-0002',
                'PT. Customer Lain',
                'PO-2024-002',
                'PROD-002',
                'Pipa Hitam 3 inch',
                '50',
                'REF-002',
                'Pending production',
                '2024-03-15',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Customer Code *',
            'Customer Name (Reference only)',
            'PO Number (Optional)',
            'Product SKU *',
            'Product Name (Reference only)',
            'Qty *',
            'Reference Number (Optional)',
            'Notes (Optional)',
            'Delivery Date (YYYY-MM-DD) *',
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
                $spreadsheet = $sheet->getParent();

                $sheet->getComment('A1')->getText()->createTextRun(
                    'Required. Kode customer, harus sama persis dengan Customer Code di master customer (contoh: CUST-0001).'
                );

                $sheet->getComment('B1')->getText()->createTextRun(
                    'Optional. Hanya untuk referensi tampilan, tidak dipakai saat import.'
                );

                $sheet->getComment('C1')->getText()->createTextRun(
                    'Optional. Nomor PO customer untuk referensi. Jika diisi akan disimpan di Delivery Schedule.'
                );

                $sheet->getComment('D1')->getText()->createTextRun(
                    'Required. Product SKU, harus sama persis dengan SKU di master product (contoh: PROD-001).'
                );

                $sheet->getComment('E1')->getText()->createTextRun(
                    'Optional. Nama produk hanya untuk referensi tampilan, tidak divalidasi saat import.'
                );

                $sheet->getComment('F1')->getText()->createTextRun(
                    'Required. Qty yang dijadwalkan. Harus angka ≥ 0 (misal: 100).'
                );

                $sheet->getComment('G1')->getText()->createTextRun(
                    'Optional. Nomor referensi internal (misal nomor kontrak / dokumen lain).'
                );

                $sheet->getComment('H1')->getText()->createTextRun(
                    'Optional. Catatan tambahan untuk jadwal pengiriman baris ini.'
                );

                $sheet->getComment('I1')->getText()->createTextRun(
                    'Required. Tanggal delivery. Boleh format tanggal Excel atau teks dengan format YYYY-MM-DD (contoh: 2024-03-01).'
                );

                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Delivery Schedule');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Delivery Schedule > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2, satu baris per jadwal pengiriman.');
                $instructionSheet->setCellValue('A6', '3. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A8', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A9', 'Customer Code *');
                $instructionSheet->setCellValue('B9', 'Wajib. Kode customer, harus sama persis dengan master customer.');
                $instructionSheet->setCellValue('A10', 'Customer Name');
                $instructionSheet->setCellValue('B10', 'Opsional. Hanya referensi, tidak divalidasi.');
                $instructionSheet->setCellValue('A11', 'PO Number');
                $instructionSheet->setCellValue('B11', 'Opsional. Nomor PO customer untuk referensi.');
                $instructionSheet->setCellValue('A12', 'Product SKU *');
                $instructionSheet->setCellValue('B12', 'Wajib. Kode SKU produk, harus sama dengan master product.');
                $instructionSheet->setCellValue('A13', 'Product Name');
                $instructionSheet->setCellValue('B13', 'Opsional. Hanya referensi tampilan.');
                $instructionSheet->setCellValue('A14', 'Qty *');
                $instructionSheet->setCellValue('B14', 'Wajib. Qty jadwal pengiriman. Harus angka ≥ 0.');
                $instructionSheet->setCellValue('A15', 'Reference Number');
                $instructionSheet->setCellValue('B15', 'Opsional. Nomor referensi internal.');
                $instructionSheet->setCellValue('A16', 'Notes');
                $instructionSheet->setCellValue('B16', 'Opsional. Catatan tambahan.');
                $instructionSheet->setCellValue('A17', 'Delivery Date *');
                $instructionSheet->setCellValue('B17', 'Wajib. Tanggal delivery, format YYYY-MM-DD atau tanggal Excel.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3:A8')->getFont()->setBold(true);
            },
        ];
    }
}
