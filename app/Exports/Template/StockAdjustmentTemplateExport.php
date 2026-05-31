<?php

namespace App\Exports\Template;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAdjustmentTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        $productsHasCodeColumn = Schema::hasColumn('products', 'code');
        $products = Product::active()->stockManaged()->orderBy('name')->take(3)->get();

        $rows = collect();
        if ($products->isEmpty()) {
            $rows->push(['PROD-001', 0, '']);
            return $rows;
        }

        foreach ($products as $product) {
            $productCode = $product->sku ?? '';
            if ($productsHasCodeColumn && !empty($product->code)) {
                $productCode = $product->code;
            }

            $rows->push([
                $productCode,
                0,
                '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Qty Actual',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

