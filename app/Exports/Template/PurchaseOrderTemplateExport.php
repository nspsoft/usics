<?php

namespace App\Exports\Template;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PurchaseOrderTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        // Pull real data from database
        $suppliers = Supplier::orderBy('name')->take(2)->get();
        $products = Product::with('unit')->orderBy('name')->take(3)->get();
        $warehouse = Warehouse::first();

        $supCode1 = $suppliers->first()?->code ?? 'SUP-001';
        $supCode2 = ($suppliers->count() > 1 ? $suppliers->last()?->code : $suppliers->first()?->code) ?? 'SUP-002';
        $whName = $warehouse?->name ?? 'Main Warehouse';
        $today = now()->format('Y-m-d');
        $expectedDate = now()->addDays(14)->format('Y-m-d');

        $rows = collect();

        if ($products->count() >= 2) {
            $p1 = $products[0];
            $p2 = $products[1];
            $p3 = $products->count() > 2 ? $products[2] : $products[0];

            // Row 1 & 2: Same supplier + warehouse + date = grouped into one PO
            $rows->push([
                '',                         // PO Number (auto-generate)
                $today,
                $expectedDate,
                $supCode1,
                $whName,
                $p1->code ?? $p1->sku,
                100,
                $p1->cost_price ?: 50000,
                0,
                'Contoh PO (baris ini & baris berikutnya jadi 1 PO karena Supplier + Warehouse + Date sama)',
            ]);
            $rows->push([
                '',
                $today,
                $expectedDate,
                $supCode1,
                $whName,
                $p2->code ?? $p2->sku,
                50,
                $p2->cost_price ?: 75000,
                5,
                '',
            ]);
            // Row 3: Different supplier = new PO
            $rows->push([
                '',
                $today,
                '',
                $supCode2,
                $whName,
                $p3->code ?? $p3->sku,
                200,
                $p3->cost_price ?: 10000,
                0,
                'Contoh PO terpisah (supplier berbeda)',
            ]);
        } else {
            // Fallback
            $p = $products->first();
            $code = $p?->code ?? $p?->sku ?? 'PROD-001';
            $price = $p?->cost_price ?: 50000;

            $rows->push(['', $today, $expectedDate, $supCode1, $whName, $code, 100, $price, 0, 'Contoh order']);
            $rows->push(['', $today, '', $supCode2, $whName, $code, 200, $price, 0, 'Contoh PO lain']);
        }

        return $rows;
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
                    'Optional. Nomor PO dari sistem lama. Jika dikosongkan, sistem akan membuat PO Number otomatis.'
                );
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Required. Tanggal PO dalam format YYYY-MM-DD atau tanggal Excel.\nBaris dengan Order Date + Supplier + Warehouse yang sama akan digabung menjadi satu PO."
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
                $sheet->getStyle('B1:H1')->getFont()->setColor($redColor);

                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Purchase Orders');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Purchase Orders > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama.');
                $instructionSheet->setCellValue('A6', '3. Baris dengan Order Date + Supplier Code + Warehouse Name yang sama akan digabung menjadi satu PO.');
                $instructionSheet->setCellValue('A7', '4. Simpan file sebagai .xlsx lalu upload kembali di form Import.');

                $instructionSheet->setCellValue('A9', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A10', 'PO Number');
                $instructionSheet->setCellValue('B10', 'Opsional. Nomor PO dari sistem lama. Jika dikosongkan, sistem akan membuat nomor otomatis.');
                $instructionSheet->setCellValue('A11', 'Order Date *');
                $instructionSheet->setCellValue('B11', 'Wajib. Tanggal PO. Format YYYY-MM-DD atau tanggal Excel.');
                $instructionSheet->setCellValue('A12', 'Expected Date');
                $instructionSheet->setCellValue('B12', 'Opsional. Perkiraan tanggal barang datang.');
                $instructionSheet->setCellValue('A13', 'Supplier Code *');
                $instructionSheet->setCellValue('B13', 'Wajib. Kode supplier sesuai master supplier.');
                $instructionSheet->setCellValue('A14', 'Warehouse Name *');
                $instructionSheet->setCellValue('B14', 'Wajib. Nama gudang sesuai master gudang.');
                $instructionSheet->setCellValue('A15', 'Product Code *');
                $instructionSheet->setCellValue('B15', 'Wajib. Kode produk sesuai master produk.');
                $instructionSheet->setCellValue('A16', 'Quantity *');
                $instructionSheet->setCellValue('B16', 'Wajib. Qty yang dipesan. Harus angka ≥ 0.');
                $instructionSheet->setCellValue('A17', 'Unit Price *');
                $instructionSheet->setCellValue('B17', 'Wajib. Harga per unit. Harus angka ≥ 0.');
                $instructionSheet->setCellValue('A18', 'Discount %');
                $instructionSheet->setCellValue('B18', 'Opsional. Persentase diskon baris ini (0-100).');
                $instructionSheet->setCellValue('A19', 'Notes');
                $instructionSheet->setCellValue('B19', 'Opsional. Catatan tambahan untuk baris PO.');

                $instructionSheet->getColumnDimension('A')->setWidth(25);
                $instructionSheet->getColumnDimension('B')->setWidth(80);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A9')->getFont()->setBold(true);
            },
        ];
    }
}
