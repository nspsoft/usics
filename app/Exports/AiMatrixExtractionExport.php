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
    protected ?int $customerId;

    public function __construct(array $items, string $monthYear = '', ?int $customerId = null)
    {
        $this->items = $items;
        $this->monthYear = $monthYear;
        $this->customerId = $customerId;
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
            'Product SKU *',
            'Product Name (Reference only)',
            'Qty *',
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
            $custId = $this->customerId ?: ($item['customer_id'] ?? null);
            if ($custId) {
                $customer = \App\Models\Customer::find($custId);
                if ($customer) {
                    $customerCode = $customer->code;
                    $customerName = $customer->name;
                }
            }

            $rows[] = [
                $customerCode,                          // Customer Code
                $customerName,                          // Customer Name
                $item['product_sku'] ?? ($item['product_code'] ?? ''),  // Product SKU
                $item['product_name'] ?? ($item['product_code'] ?? ''), // Product Name
                $item['qty'] ?? 0,                      // Qty
                $item['date'] ?? '',                    // Delivery Date
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->items) + 1;

        // Header styling (amber, matching the AI theme)
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Data styling
        if ($lastRow > 1) {
            $sheet->getStyle("A2:F{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Highlight rows with missing data (customer_code or product_sku empty or not matched)
            for ($row = 2; $row <= $lastRow; $row++) {
                $custCode = $sheet->getCell("A{$row}")->getValue();
                $prodSku = $sheet->getCell("C{$row}")->getValue();
                $itemIndex = $row - 2;
                $item = $this->items[$itemIndex] ?? [];
                $isMatched = ($item['match_status'] ?? 'NO_MATCH') === 'MATCHED';

                if (empty($custCode) || empty($prodSku) || !$isMatched) {
                    $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    ]);
                }
            }
        }

        return [];
    }
}
