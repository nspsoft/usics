<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductDataExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Product::with(['category', 'unit'])->get()->map(function ($product) {
            return [
                $product->sku,
                $product->name,
                $product->description,
                $product->barcode,
                $product->category ? $product->category->name : '',
                $product->unit ? $product->unit->symbol : '',
                $product->type, // product, service, consumable
                $product->product_type, // raw_material, etc.
                $product->cost_price,
                $product->selling_price,
                $product->min_stock,
                $product->reorder_point,
                $product->reorder_qty,
                $product->max_stock,
                $product->lead_time_days,
                $product->weight,
                $product->weight_unit,
                $product->length,
                $product->width,
                $product->height,
                $product->dimension_unit,
                $product->is_manufactured ? 'Yes' : 'No',
                $product->is_purchased ? 'Yes' : 'No',
                $product->is_sold ? 'Yes' : 'No',
                $product->track_serial ? 'Yes' : 'No',
                $product->track_batch ? 'Yes' : 'No',
                $product->track_expiry ? 'Yes' : 'No',
            ];
        });
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
                
                // 2. Data Validation (Dropdowns)
                // Item Type (Column G)
                $validation = $sheet->getCell('G2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"product,service,consumable"');
                // Apply to rows 2-1000
                $sheet->setDataValidation('G2:G1000', $validation);

                // Product Type (Column H)
                $validation2 = $sheet->getCell('H2')->getDataValidation();
                $validation2->setType(DataValidation::TYPE_LIST);
                $validation2->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation2->setAllowBlank(false);
                $validation2->setShowInputMessage(true);
                $validation2->setShowErrorMessage(true);
                $validation2->setShowDropDown(true);
                $validation2->setFormula1('"raw_material,wip,finished_good,spare_part"');
                $sheet->setDataValidation('H2:H1000', $validation2);

                // Boolean Fields (Yes/No) - Columns V, W, X, Y, Z, AA
                $validation3 = $sheet->getCell('V2')->getDataValidation();
                $validation3->setType(DataValidation::TYPE_LIST);
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
            },
        ];
    }
}
