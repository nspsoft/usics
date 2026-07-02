<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Company;
use App\Models\Unit;

$company = Company::first();
$companyId = $company ? $company->id : null;

$categoryRm = Category::where('code', 'RM')->first();
$categoryRmId = $categoryRm->id;

$unitKg = Unit::where('code', 'KG')->first();
$unitKgId = $unitKg->id;

$coils = [
    [
        'sku' => 'COIL-HR-SPHC-3.0',
        'name' => 'Hot Rolled Coil SPHC 3.0mm x 1219mm',
        'description' => 'Baja canai panas gulungan standar SPHC ketebalan 3.0mm, lebar 1219mm.',
        'category_id' => $categoryRmId,
        'type' => 'product',
        'product_type' => 'raw_material',
        'unit_id' => $unitKgId,
        'purchase_unit_id' => $unitKgId,
        'sales_unit_id' => $unitKgId,
        'cost_price' => 11200.00,
        'selling_price' => 0.00,
        'min_stock' => 60000.00,
        'max_stock' => 600000.00,
        'reorder_point' => 120000.00,
        'reorder_qty' => 180000.00,
        'weight' => 22000.00,
        'weight_unit' => 'kg',
        'is_manufactured' => false,
        'is_purchased' => true,
        'is_sold' => false,
        'track_batch' => true,
        'attributes' => [
            'grade' => 'SPHC',
            'thickness' => 3.0,
            'width' => 1219,
            'surface_finish' => 'Black/Oiled'
        ]
    ],
    [
        'sku' => 'COIL-HR-SPHC-4.0',
        'name' => 'Hot Rolled Coil SPHC 4.0mm x 1219mm',
        'description' => 'Baja canai panas gulungan standar SPHC ketebalan 4.0mm, lebar 1219mm.',
        'category_id' => $categoryRmId,
        'type' => 'product',
        'product_type' => 'raw_material',
        'unit_id' => $unitKgId,
        'purchase_unit_id' => $unitKgId,
        'sales_unit_id' => $unitKgId,
        'cost_price' => 11000.00,
        'selling_price' => 0.00,
        'min_stock' => 40000.00,
        'max_stock' => 400000.00,
        'reorder_point' => 80000.00,
        'reorder_qty' => 120000.00,
        'weight' => 24000.00,
        'weight_unit' => 'kg',
        'is_manufactured' => false,
        'is_purchased' => true,
        'is_sold' => false,
        'track_batch' => true,
        'attributes' => [
            'grade' => 'SPHC',
            'thickness' => 4.0,
            'width' => 1219,
            'surface_finish' => 'Black/Oiled'
        ]
    ],
    [
        'sku' => 'COIL-CR-SPCC-0.8',
        'name' => 'Cold Rolled Coil SPCC-SD 0.8mm x 1219mm',
        'description' => 'Baja canai dingin gulungan standar SPCC-SD ketebalan 0.8mm, lebar 1219mm.',
        'category_id' => $categoryRmId,
        'type' => 'product',
        'product_type' => 'raw_material',
        'unit_id' => $unitKgId,
        'purchase_unit_id' => $unitKgId,
        'sales_unit_id' => $unitKgId,
        'cost_price' => 13800.00,
        'selling_price' => 0.00,
        'min_stock' => 30000.00,
        'max_stock' => 300000.00,
        'reorder_point' => 60000.00,
        'reorder_qty' => 90000.00,
        'weight' => 15000.00,
        'weight_unit' => 'kg',
        'is_manufactured' => false,
        'is_purchased' => true,
        'is_sold' => false,
        'track_batch' => true,
        'attributes' => [
            'grade' => 'SPCC-SD',
            'thickness' => 0.8,
            'width' => 1219,
            'surface_finish' => 'Dull Finish'
        ]
    ],
    [
        'sku' => 'COIL-CR-SPCC-1.0',
        'name' => 'Cold Rolled Coil SPCC-SD 1.0mm x 1219mm',
        'description' => 'Baja canai dingin gulungan standar SPCC-SD ketebalan 1.0mm, lebar 1219mm.',
        'category_id' => $categoryRmId,
        'type' => 'product',
        'product_type' => 'raw_material',
        'unit_id' => $unitKgId,
        'purchase_unit_id' => $unitKgId,
        'sales_unit_id' => $unitKgId,
        'cost_price' => 13500.00,
        'selling_price' => 0.00,
        'min_stock' => 45000.00,
        'max_stock' => 450000.00,
        'reorder_point' => 90000.00,
        'reorder_qty' => 135000.00,
        'weight' => 18000.00,
        'weight_unit' => 'kg',
        'is_manufactured' => false,
        'is_purchased' => true,
        'is_sold' => false,
        'track_batch' => true,
        'attributes' => [
            'grade' => 'SPCC-SD',
            'thickness' => 1.0,
            'width' => 1219,
            'surface_finish' => 'Dull Finish'
        ]
    ],
    [
        'sku' => 'COIL-GA-SGCC-1.2',
        'name' => 'Galvannealed Coil SGCC 1.2mm x 1219mm',
        'description' => 'Baja berlapis Galvannealed tahan korosi ketebalan 1.2mm, lebar 1219mm.',
        'category_id' => $categoryRmId,
        'type' => 'product',
        'product_type' => 'raw_material',
        'unit_id' => $unitKgId,
        'purchase_unit_id' => $unitKgId,
        'sales_unit_id' => $unitKgId,
        'cost_price' => 15000.00,
        'selling_price' => 0.00,
        'min_stock' => 25000.00,
        'max_stock' => 250000.00,
        'reorder_point' => 50000.00,
        'reorder_qty' => 75000.00,
        'weight' => 20000.00,
        'weight_unit' => 'kg',
        'is_manufactured' => false,
        'is_purchased' => true,
        'is_sold' => false,
        'track_batch' => true,
        'attributes' => [
            'grade' => 'SGCC',
            'thickness' => 1.2,
            'width' => 1219,
            'coating_weight' => 'F06'
        ]
    ]
];

foreach ($coils as $c) {
    Product::updateOrCreate(
        [
            'company_id' => $companyId,
            'sku' => $c['sku']
        ],
        array_merge($c, ['is_active' => true])
    );
    echo "Seeded " . $c['sku'] . "\n";
}

echo "Successfully seeded 5 additional coils!\n";
