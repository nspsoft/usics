<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Data mutasi bank supplier (Debit / DB) untuk uji coba
$headers = ['Tanggal', 'Keterangan Mutasi', 'Kode Cabang', 'Nominal Transfer', 'DB/CR'];
$data = [
    // 1. Cocok dengan Purchase Invoice DEMO-PINV-307 (CV. Mandiri Mitra Sejati)
    ['21/06', 'DEBIT TRF DR BI-FAST KE MANDIRI MITRA SEJATI PELUNASAN DEMO-PINV-307', '0015', '13875000', 'DB'],
    
    // 2. Cocok dengan Purchase Invoice DEMO-PINV-308 (PT. Armstrong Industri Indonesia)
    ['21/06', 'TRF MANDIRI PT ARMSTRONG INDONESIA INV 308', '0015', '24500000', 'DB'],
    
    // 3. Cocok dengan Purchase Invoice DEMO-PINV-309 (PT. Artha Dipta Utama)
    ['21/06', 'PEMBAYARAN HUTANG KEPADA PT ARTHA DIPTA UTAMA NOTA 309', '0015', '8620000', 'DB'],
    
    // 4. Mutasi dummy yang tidak cocok (biaya admin)
    ['21/06', 'DEBIT BIAYA ADMIN BULANAN BANK BCA', '0015', '15000', 'DB']
];

// --- 1. Generate CSV File ---
$csvPath = 'public/trial_mutasi_supplier_bca.csv';
$csvFile = fopen($csvPath, 'w');
fputcsv($csvFile, $headers);
foreach ($data as $row) {
    fputcsv($csvFile, $row);
}
fclose($csvFile);
echo "CSV file generated at: {$csvPath}\n";

// --- 2. Generate XLSX File ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Write headers
foreach ($headers as $colIdx => $header) {
    $sheet->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
}

// Write rows
foreach ($data as $rowIdx => $row) {
    foreach ($row as $colIdx => $val) {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowIdx + 2, $val);
    }
}

// Auto-size columns
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$xlsxPath = 'public/trial_mutasi_supplier_bca.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($xlsxPath);
echo "XLSX file generated at: {$xlsxPath}\n";
