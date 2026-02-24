<?php

namespace App\Exports\Template;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return collect([
            [
                'RM-STEEL-001',             // SKU
                'Steel Plate 2mm',          // Name
                'High quality steel plate', // Description
                '8991234567001',            // Barcode
                'Raw Materials',            // Category
                'kg',                       // Unit
                'product',                  // Item Type
                'raw_material',             // Product Type
                15000,                      // Cost Price
                0,                          // Selling Price
                100,                        // Min Stock
                200,                        // Reorder Point
                500,                        // Reorder Qty
                1000,                       // Max Stock
                3,                          // Lead Time (Days)
                1,                          // Weight
                'kg',                       // Weight Unit
                100,                        // Length
                100,                        // Width
                2,                          // Height
                'cm',                       // Dimension Unit
                'No',                       // Is Manufactured
                'Yes',                      // Is Purchased
                'No',                       // Is Sold
                'No',                       // Track Serial
                'Yes',                      // Track Batch
                'No',                       // Track Expiry
                'PT. Customer A',           // Customer Name (Optional)
                'CV. Supplier B',           // Supplier Name (Optional)
            ],
            [
                'FG-TABLE-001',             // SKU
                'Executive Office Table',   // Name
                'Mahogany wood table',      // Description
                '8991234567002',            // Barcode
                'Finished Goods',           // Category
                'pcs',                      // Unit
                'product',                  // Item Type
                'finished_good',            // Product Type
                1500000,                    // Cost Price
                2500000,                    // Selling Price
                10,                         // Min Stock
                15,                         // Reorder Point
                20,                         // Reorder Qty
                50,                         // Max Stock
                7,                          // Lead Time (Days)
                25,                         // Weight
                'kg',                       // Weight Unit
                120,                        // Length
                60,                         // Width
                75,                         // Height
                'cm',                       // Dimension Unit
                'Yes',                      // Is Manufactured
                'No',                       // Is Purchased
                'Yes',                      // Is Sold
                'Yes',                      // Track Serial
                'No',                       // Track Batch
                'No',                       // Track Expiry
                '',                         // Customer Name
                '',                         // Supplier Name
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Description',
            'Barcode',
            'Category',
            'Unit',
            'Item Type',
            'Product Type',
            'Cost Price',
            'Selling Price',
            'Min Stock',
            'Reorder Point',
            'Reorder Qty',
            'Max Stock',
            'Lead Time (Days)',
            'Weight',
            'Weight Unit',
            'Length',
            'Width',
            'Height',
            'Dimension Unit',
            'Is Manufactured',
            'Is Purchased',
            'Is Sold',
            'Track Serial',
            'Track Batch',
            'Track Expiry',
            'Customer Name',
            'Supplier Name',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Add Comments (Instructions)
                $sheet->getComment('G1')->getText()->createTextRun("Options:\n- product\n- service\n- consumable");
                $sheet->getComment('H1')->getText()->createTextRun("Options:\n- raw_material\n- wip\n- finished_good\n- spare_part");
                $sheet->getComment('V1')->getText()->createTextRun("Fill with 'Yes' or 'No'");
                $sheet->getComment('AB1')->getText()->createTextRun("Optional: Exclusive Customer Name");
                $sheet->getComment('AC1')->getText()->createTextRun("Optional: Preferred Supplier Name");
                
                // 2. Data Validation (Dropdowns)
                // Item Type (Column G)
                $validation = $sheet->getCell('G2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"product,service,consumable"');
                // Apply to rows 2-1000
                $sheet->setDataValidation('G2:G1000', $validation);

                // Product Type (Column H)
                $validation2 = $sheet->getCell('H2')->getDataValidation();
                $validation2->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation2->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation2->setAllowBlank(false);
                $validation2->setShowInputMessage(true);
                $validation2->setShowErrorMessage(true);
                $validation2->setShowDropDown(true);
                $validation2->setFormula1('"raw_material,wip,finished_good,spare_part"');
                $sheet->setDataValidation('H2:H1000', $validation2);

                // Boolean Fields (Yes/No) - Columns V, W, X, Y, Z, AA
                $validation3 = $sheet->getCell('V2')->getDataValidation();
                $validation3->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation3->setAllowBlank(true);
                $validation3->setShowDropDown(true);
                $validation3->setFormula1('"Yes,No"');
                
                $sheet->setDataValidation('V2:V1000', $validation3); // Is Manufactured
                $sheet->setDataValidation('W2:W1000', $validation3); // Is Purchased
                $sheet->setDataValidation('X2:X1000', $validation3); // Is Sold
                $sheet->setDataValidation('Y2:Y1000', $validation3); // Track Serial
                $sheet->setDataValidation('Z2:Z1000', $validation3); // Track Batch
                $sheet->setDataValidation('AA2:AA1000', $validation3); // Track Expiry
                $sheet->setDataValidation('AA2:AA1000', $validation3); // Track Expiry

                // 3. Visual Cues (Mandatory Fields = Red & Bold)
                // Mandatory: SKU (A1), Name (B1), Item Type (G1), Product Type (H1)
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
                $sheet->getStyle('G1:H1')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));
                
                // Optional: Standard Black Bold
                $sheet->getStyle('C1:F1')->getFont()->setBold(true);
                $sheet->getStyle('I1:AA1')->getFont()->setBold(true);

                // 4. Instruction Sheet
                $spreadsheet = $sheet->getParent();
                $instructionSheet = $spreadsheet->createSheet();
                $instructionSheet->setTitle('Instruction');

                $instructionSheet->setCellValue('A1', 'Instruksi Import Products');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructionSheet->setCellValue('A3', 'Langkah umum:');
                $instructionSheet->setCellValue('A4', '1. Download template ini dari menu Inventory > Products > Import.');
                $instructionSheet->setCellValue('A5', '2. Isi data mulai dari baris ke-2 di sheet utama (jangan mengubah header).');
                $instructionSheet->setCellValue('A6', '3. SKU harus unik per produk. Jika SKU sudah ada dan opsi overwrite aktif, data produk akan diperbarui.');
                $instructionSheet->setCellValue('A7', '4. Pastikan Category, Unit, dan Unit lain yang dipakai sudah ada di master.');
                $instructionSheet->setCellValue('A8', '5. Simpan file sebagai .xlsx lalu upload kembali di form Import Products.');

                $instructionSheet->setCellValue('A10', 'Keterangan kolom:');
                $instructionSheet->setCellValue('A11', 'SKU *');
                $instructionSheet->setCellValue('B11', 'Wajib. Kode unik produk. Digunakan sebagai kunci utama saat import.');
                $instructionSheet->setCellValue('A12', 'Name *');
                $instructionSheet->setCellValue('B12', 'Wajib. Nama produk yang akan tampil di laporan dan transaksi.');
                $instructionSheet->setCellValue('A13', 'Description');
                $instructionSheet->setCellValue('B13', 'Opsional. Deskripsi tambahan produk.');
                $instructionSheet->setCellValue('A14', 'Barcode');
                $instructionSheet->setCellValue('B14', 'Opsional. Barcode produk jika digunakan.');
                $instructionSheet->setCellValue('A15', 'Category');
                $instructionSheet->setCellValue('B15', 'Opsional. Nama kategori produk. Jika kosong, kategori akan dibiarkan null.');
                $instructionSheet->setCellValue('A16', 'Unit');
                $instructionSheet->setCellValue('B16', 'Opsional. Kode satuan dasar (mis. PCS, KG). Jika kosong, sistem dapat menggunakan default.');
                $instructionSheet->setCellValue('A17', 'Item Type *');
                $instructionSheet->setCellValue('B17', "Wajib. Jenis item: product, service, atau consumable. Gunakan dropdown di sheet utama.");
                $instructionSheet->setCellValue('A18', 'Product Type *');
                $instructionSheet->setCellValue('B18', "Wajib. Tipe produk: raw_material, wip, finished_good, atau spare_part. Gunakan dropdown.");
                $instructionSheet->setCellValue('A19', 'Cost Price');
                $instructionSheet->setCellValue('B19', 'Opsional. Harga pokok standar. Bisa diisi 0 jika belum ditentukan.');
                $instructionSheet->setCellValue('A20', 'Selling Price');
                $instructionSheet->setCellValue('B20', 'Opsional. Harga jual standar yang akan dipakai sebagai default di Sales Order jika tidak ada harga kontrak.');
                $instructionSheet->setCellValue('A21', 'Min Stock / Reorder Point / Reorder Qty / Max Stock');
                $instructionSheet->setCellValue('B21', 'Opsional. Pengaturan stok minimum, titik pemesanan ulang, qty pemesanan ulang, dan stok maksimum.');
                $instructionSheet->setCellValue('A22', 'Lead Time (Days)');
                $instructionSheet->setCellValue('B22', 'Opsional. Estimasi waktu pemenuhan pembelian/produksi dalam hari.');
                $instructionSheet->setCellValue('A23', 'Weight & Weight Unit');
                $instructionSheet->setCellValue('B23', 'Opsional. Berat produk dan satuannya (mis. kg).');
                $instructionSheet->setCellValue('A24', 'Length / Width / Height / Dimension Unit');
                $instructionSheet->setCellValue('B24', 'Opsional. Dimensi produk dan satuannya (mis. cm).');
                $instructionSheet->setCellValue('A25', 'Is Manufactured / Is Purchased / Is Sold');
                $instructionSheet->setCellValue('B25', "Opsional. Diisi 'Yes' atau 'No'. Menentukan apakah produk bisa diproduksi, dibeli, atau dijual.");
                $instructionSheet->setCellValue('A26', 'Track Serial / Track Batch / Track Expiry');
                $instructionSheet->setCellValue('B26', "Opsional. Diisi 'Yes' atau 'No'. Mengaktifkan penelusuran nomor seri, batch, atau kadaluarsa.");
                $instructionSheet->setCellValue('A27', 'Customer Name');
                $instructionSheet->setCellValue('B27', "Opsional. Nama Customer untuk produk eksklusif. Harus sesuai dengan nama di master Customer.");
                $instructionSheet->setCellValue('A28', 'Supplier Name');
                $instructionSheet->setCellValue('B28', "Opsional. Nama Supplier utama. Harus sesuai dengan nama di master Supplier.");

                $instructionSheet->getColumnDimension('A')->setWidth(40);
                $instructionSheet->getColumnDimension('B')->setWidth(110);
                $instructionSheet->getStyle('A3')->getFont()->setBold(true);
                $instructionSheet->getStyle('A10')->getFont()->setBold(true);
            },
        ];
    }
}
