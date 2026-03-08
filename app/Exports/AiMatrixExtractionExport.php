<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AiMatrixExtractionExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $items;
    protected string $monthYear;

    public function __construct(array $items, string $monthYear = '')
    {
        $this->items = $items;
        $this->monthYear = $monthYear;
    }

    public function title(): string
    {
        return 'AI Extraction Result';
    }

    /**
     * Headings match the Import Delivery Schedule template exactly.
     */
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

    public function array(): array
    {
        $rows = [];
        foreach ($this->items as $item) {
            // Map customer: use matched customer code, or fallback to supplier_name from AI
            $customerCode = '';
            $customerName = $item['supplier_name'] ?? '';
            if (isset($item['customer_id']) && $item['customer_id']) {
                $customer = \App\Models\Customer::find($item['customer_id']);
                if ($customer) {
                    $customerCode = $customer->code;
                    $customerName = $customer->name;
                }
            }

            $rows[] = [
                $customerCode,                          // Customer Code
                $customerName,                          // Customer Name
                '',                                     // PO Number
                $item['product_sku'] ?? ($item['product_code'] ?? ''),  // Product SKU
                $item['product_name'] ?? ($item['product_code'] ?? ''), // Product Name
                $item['qty'] ?? 0,                      // Qty
                '',                                     // Reference Number
                ($item['match_status'] ?? 'NO_MATCH') !== 'MATCHED'
                    ? 'AI: ' . ($item['product_code'] ?? '') . ' - BELUM TERDAFTAR'
                    : '',                               // Notes (flag unmatched)
                $item['date'] ?? '',                    // Delivery Date
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->items) + 1;

        // Header styling (amber, matching the AI theme)
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Data styling
        if ($lastRow > 1) {
            $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Highlight rows with missing data (customer_code or product_sku empty)
            for ($row = 2; $row <= $lastRow; $row++) {
                $custCode = $sheet->getCell("A{$row}")->getValue();
                $prodSku = $sheet->getCell("D{$row}")->getValue();
                $notes = $sheet->getCell("H{$row}")->getValue();

                if (empty($custCode) || empty($prodSku) || str_contains($notes, 'BELUM TERDAFTAR')) {
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    ]);
                    if (!empty($notes)) {
                        $sheet->getStyle("H{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => 'DC2626'], 'italic' => true],
                        ]);
                    }
                }
            }
        }

        return [];
    }
}
