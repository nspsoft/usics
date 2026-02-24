<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Customer;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Str;

class ProductImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    private $overwrite;

    public function __construct($overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function model(array $row)
    {
        // Skip if mandatory fields are missing
        if (!isset($row['sku']) || !isset($row['name'])) {
            return null;
        }

        // Find or create Category
        $categoryName = $row['category'] ?? 'Uncategorized';
        $category = Category::firstOrCreate(
            ['name' => $categoryName],
            ['code' => Str::upper(Str::slug($categoryName))]
        );

        // Find or create Unit
        $unitSymbol = $row['unit'] ?? 'pcs';
        $unit = Unit::firstOrCreate(
            ['symbol' => $unitSymbol],
            [
                'name' => $unitSymbol,
                'code' => Str::upper(Str::slug($unitSymbol))
            ]
        );

        // Find Customer (Optional)
        $customerId = null;
        if (!empty($row['customer_name'])) {
            $customer = Customer::where('name', $row['customer_name'])->first();
            $customerId = $customer?->id;
        }

        // Find Supplier (Optional)
        $supplierId = null;
        if (!empty($row['supplier_name'])) {
            $supplier = Supplier::where('name', $row['supplier_name'])->first();
            $supplierId = $supplier?->id;
        }

        $productData = [
            'name'           => $row['name'],
            'description'    => $row['description'] ?? null,
            'barcode'        => $row['barcode'] ?? null,
            'category_id'    => $category->id,
            'customer_id'    => $customerId,
            'supplier_id'    => $supplierId,
            'unit_id'        => $unit->id,
            'type'           => $row['item_type'] ?? 'product', // product, service, consumable
            'product_type'   => $row['product_type'] ?? 'raw_material', // raw_material, wip, finished_good, spare_part
            'cost_price'     => $this->parseNumeric($row['cost_price'] ?? 0),
            'selling_price'  => $this->parseNumeric($row['selling_price'] ?? 0),
            'min_stock'      => $this->parseNumeric($row['min_stock'] ?? 0),
            'reorder_point'  => $this->parseNumeric($row['reorder_point'] ?? 0),
            'reorder_qty'    => $this->parseNumeric($row['reorder_qty'] ?? 0),
            'max_stock'      => $this->parseNumeric($row['max_stock'] ?? 0),
            'lead_time_days' => $this->parseNumeric($row['lead_time_days'] ?? 0),
            
            // Physical
            'weight'         => $this->parseNumeric($row['weight'] ?? null),
            'weight_unit'    => $row['weight_unit'] ?? 'kg',
            'length'         => $this->parseNumeric($row['length'] ?? null),
            'width'          => $this->parseNumeric($row['width'] ?? null),
            'height'         => $this->parseNumeric($row['height'] ?? null),
            'dimension_unit' => $row['dimension_unit'] ?? 'cm',

            // Boolean Flags
            'is_manufactured' => $this->parseBoolean($row['is_manufactured'] ?? false),
            'is_purchased'    => $this->parseBoolean($row['is_purchased'] ?? true),
            'is_sold'         => $this->parseBoolean($row['is_sold'] ?? true),
            'track_serial'    => $this->parseBoolean($row['track_serial'] ?? false),
            'track_batch'     => $this->parseBoolean($row['track_batch'] ?? false),
            'track_expiry'    => $this->parseBoolean($row['track_expiry'] ?? false),
            'is_active'       => true,
            // SKU is used for matching, so it's not needed in update values usually, but for create it is.
            // But updateOrCreate takes match attributes as first arg.
        ];

        if ($this->overwrite) {
            return Product::updateOrCreate(
                ['sku' => $row['sku']], // Match by SKU
                $productData
            );
        }

        // Default behavior: Create new (will throw error if SKU exists)
        return new Product(array_merge(['sku' => $row['sku']], $productData));
    }

    private function parseNumeric($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        
        // Remove everything except numbers, dots, and minus sign
        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        
        return is_numeric($clean) ? $clean : 0;
    }

    private function parseBoolean($value)
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return $value > 0;
        if (is_string($value)) {
            return in_array(strtolower($value), ['yes', 'true', '1', 'y']);
        }
        return false;
    }
}
