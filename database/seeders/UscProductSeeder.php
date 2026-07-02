<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UscProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get default company
        $company = Company::first();
        $companyId = $company ? $company->id : null;

        // 2. Fetch categories
        $categoryRm = Category::where('code', 'RM')->first();
        $categoryWip = Category::where('code', 'WIP')->first();
        $categoryFg = Category::where('code', 'FG')->first();
        $categorySp = Category::where('code', 'SP')->first();

        // Fallbacks in case code does not match
        $categoryRmId = $categoryRm ? $categoryRm->id : Category::firstOrCreate(['code' => 'RM'], ['name' => 'Raw Materials', 'type' => 'product', 'company_id' => $companyId])->id;
        $categoryWipId = $categoryWip ? $categoryWip->id : Category::firstOrCreate(['code' => 'WIP'], ['name' => 'Work in Progress', 'type' => 'product', 'company_id' => $companyId])->id;
        $categoryFgId = $categoryFg ? $categoryFg->id : Category::firstOrCreate(['code' => 'FG'], ['name' => 'Finished Goods', 'type' => 'product', 'company_id' => $companyId])->id;
        $categorySpId = $categorySp ? $categorySp->id : Category::firstOrCreate(['code' => 'SP'], ['name' => 'Spare Parts', 'type' => 'product', 'company_id' => $companyId])->id;

        // 3. Fetch units
        $unitKg = Unit::where('code', 'KG')->first();
        $unitPcs = Unit::where('code', 'PCS')->first();
        $unitSht = Unit::where('code', 'SHT')->first();
        $unitRoll = Unit::where('code', 'ROLL')->first();

        // Fallbacks
        $unitKgId = $unitKg ? $unitKg->id : Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilogram', 'symbol' => 'kg', 'company_id' => $companyId])->id;
        $unitPcsId = $unitPcs ? $unitPcs->id : Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'symbol' => 'pcs', 'company_id' => $companyId])->id;
        $unitShtId = $unitSht ? $unitSht->id : Unit::firstOrCreate(['code' => 'SHT'], ['name' => 'Sheet', 'symbol' => 'sht', 'company_id' => $companyId])->id;
        $unitRollId = $unitRoll ? $unitRoll->id : Unit::firstOrCreate(['code' => 'ROLL'], ['name' => 'Roll', 'symbol' => 'roll', 'company_id' => $companyId])->id;

        // 4. Products Data definitions
        $products = [
            // ==========================================
            // RAW MATERIALS (Baja Coil Raksasa)
            // ==========================================
            [
                'sku' => 'COIL-HR-SPHC-2.0',
                'name' => 'Hot Rolled Coil SPHC 2.0mm x 1219mm',
                'description' => 'Baja canai panas gulungan standar SPHC ketebalan 2.0mm, lebar 1219mm. Cocok untuk komponen struktural otomotif.',
                'category_id' => $categoryRmId,
                'type' => 'product',
                'product_type' => 'raw_material',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 11500.00,
                'selling_price' => 0.00, // Raw material typically not directly sold
                'min_stock' => 50000.00,
                'max_stock' => 500000.00,
                'reorder_point' => 100000.00,
                'reorder_qty' => 150000.00,
                'weight' => 22000.00, // Berat per coil rata-rata 22 ton (22,000 Kg)
                'weight_unit' => 'kg',
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'grade' => 'SPHC',
                    'thickness' => 2.0,
                    'width' => 1219,
                    'surface_finish' => 'Black/Oiled'
                ]
            ],
            [
                'sku' => 'COIL-CR-SPCC-1.2',
                'name' => 'Cold Rolled Coil SPCC-SD 1.2mm x 1219mm',
                'description' => 'Baja canai dingin gulungan standar SPCC-SD ketebalan 1.2mm, lebar 1219mm. Sangat baik untuk permukaan bodi luar.',
                'category_id' => $categoryRmId,
                'type' => 'product',
                'product_type' => 'raw_material',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 13200.00,
                'selling_price' => 0.00,
                'min_stock' => 40000.00,
                'max_stock' => 400000.00,
                'reorder_point' => 80000.00,
                'reorder_qty' => 120000.00,
                'weight' => 18000.00,
                'weight_unit' => 'kg',
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'grade' => 'SPCC-SD',
                    'thickness' => 1.2,
                    'width' => 1219,
                    'surface_finish' => 'Dull Finish'
                ]
            ],
            [
                'sku' => 'COIL-GA-SGCC-0.8',
                'name' => 'Galvannealed Coil SGCC 0.8mm x 1219mm',
                'description' => 'Baja berlapis Galvannealed tahan korosi ketebalan 0.8mm, lebar 1219mm. Standard aplikasi bodi luar otomotif tahan karat.',
                'category_id' => $categoryRmId,
                'type' => 'product',
                'product_type' => 'raw_material',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 15000.00,
                'selling_price' => 0.00,
                'min_stock' => 30000.00,
                'max_stock' => 300000.00,
                'reorder_point' => 60000.00,
                'reorder_qty' => 100000.00,
                'weight' => 15000.00,
                'weight_unit' => 'kg',
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'grade' => 'SGCC',
                    'thickness' => 0.8,
                    'width' => 1219,
                    'coating_weight' => 'F06'
                ]
            ],

            // ==========================================
            // WORK IN PROGRESS / WIP (Hasil Slitting - Strips)
            // ==========================================
            [
                'sku' => 'SLIT-CR-SPCC-1.2-300',
                'name' => 'Slitted Strip SPCC 1.2mm x 300mm',
                'description' => 'Hasil slitting dari COIL-CR-SPCC-1.2 dipotong menjadi lebar 300mm. Siap dikirim ke lini blanking.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 13700.00, // cost_price includes processing cost
                'selling_price' => 0.00,
                'min_stock' => 10000.00,
                'max_stock' => 100000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-CR-SPCC-1.2',
                    'thickness' => 1.2,
                    'width' => 300,
                    'process' => 'Slitting'
                ]
            ],
            [
                'sku' => 'SLIT-HR-SPHC-2.0-150',
                'name' => 'Slitted Strip SPHC 2.0mm x 150mm',
                'description' => 'Hasil slitting presisi dari COIL-HR-SPHC-2.0 dipotong memanjang menjadi lebar 150mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 12100.00,
                'selling_price' => 0.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-HR-SPHC-2.0',
                    'thickness' => 2.0,
                    'width' => 150,
                    'process' => 'Slitting'
                ]
            ],
            [
                'sku' => 'SLIT-HR-SPHC-2.0-200',
                'name' => 'Slitted Strip SPHC 2.0mm x 200mm',
                'description' => 'Hasil slitting presisi dari COIL-HR-SPHC-2.0 dipotong memanjang menjadi lebar 200mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 12000.00,
                'selling_price' => 0.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-HR-SPHC-2.0',
                    'thickness' => 2.0,
                    'width' => 200,
                    'process' => 'Slitting'
                ]
            ],
            [
                'sku' => 'SLIT-CR-SPCC-1.2-120',
                'name' => 'Slitted Strip SPCC 1.2mm x 120mm',
                'description' => 'Hasil slitting presisi dari COIL-CR-SPCC-1.2 dipotong memanjang menjadi lebar 120mm untuk komponen bracket.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 13800.00,
                'selling_price' => 0.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-CR-SPCC-1.2',
                    'thickness' => 1.2,
                    'width' => 120,
                    'process' => 'Slitting'
                ]
            ],
            [
                'sku' => 'SLIT-GA-SGCC-0.8-250',
                'name' => 'Slitted Strip SGCC 0.8mm x 250mm',
                'description' => 'Hasil slitting presisi dari COIL-GA-SGCC-0.8 dipotong memanjang menjadi lebar 250mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 15550.00,
                'selling_price' => 0.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-GA-SGCC-0.8',
                    'thickness' => 0.8,
                    'width' => 250,
                    'process' => 'Slitting'
                ]
            ],
            [
                'sku' => 'SLIT-GA-SGCC-0.8-400',
                'name' => 'Slitted Strip SGCC 0.8mm x 400mm',
                'description' => 'Hasil slitting presisi dari COIL-GA-SGCC-0.8 dipotong memanjang menjadi lebar 400mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitKgId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitKgId,
                'cost_price' => 15500.00,
                'selling_price' => 0.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-GA-SGCC-0.8',
                    'thickness' => 0.8,
                    'width' => 400,
                    'process' => 'Slitting'
                ]
            ],

            // ==========================================
            // WORK IN PROGRESS / WIP (Hasil Shearing - Sheets)
            // ==========================================
            [
                'sku' => 'SHT-GA-SGCC-0.8-1200x2400',
                'name' => 'Steel Sheet GA 0.8mm x 1200mm x 2400mm',
                'description' => 'Hasil shearing lembaran baja dari COIL-GA-SGCC-0.8 ukuran 1200mm x 2400mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitShtId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitShtId,
                'cost_price' => 280000.00, // Harga per sheet
                'selling_price' => 0.00,
                'min_stock' => 500.00,
                'max_stock' => 5000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-GA-SGCC-0.8',
                    'thickness' => 0.8,
                    'length' => 2400,
                    'width' => 1200,
                    'process' => 'Shearing'
                ]
            ],
            [
                'sku' => 'SHT-HR-SPHC-2.0-1219x2438',
                'name' => 'Steel Sheet SPHC 2.0mm x 1219mm x 2438mm',
                'description' => 'Hasil shearing lembaran baja canai panas dari COIL-HR-SPHC-2.0 ukuran standard 4x8 kaki (1219mm x 2438mm).',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitShtId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitShtId,
                'cost_price' => 555000.00,
                'selling_price' => 0.00,
                'min_stock' => 300.00,
                'max_stock' => 3000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-HR-SPHC-2.0',
                    'thickness' => 2.0,
                    'length' => 2438,
                    'width' => 1219,
                    'process' => 'Shearing'
                ]
            ],
            [
                'sku' => 'SHT-CR-SPCC-1.2-1219x2438',
                'name' => 'Steel Sheet SPCC 1.2mm x 1219mm x 2438mm',
                'description' => 'Hasil shearing lembaran baja canai dingin dari COIL-CR-SPCC-1.2 ukuran standard 4x8 kaki (1219mm x 2438mm).',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitShtId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitShtId,
                'cost_price' => 382000.00,
                'selling_price' => 0.00,
                'min_stock' => 400.00,
                'max_stock' => 4000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-CR-SPCC-1.2',
                    'thickness' => 1.2,
                    'length' => 2438,
                    'width' => 1219,
                    'process' => 'Shearing'
                ]
            ],
            [
                'sku' => 'SHT-GA-SGCC-0.8-1000x2000',
                'name' => 'Steel Sheet SGCC 0.8mm x 1000mm x 2000mm',
                'description' => 'Hasil shearing lembaran baja berlapis dari COIL-GA-SGCC-0.8 ukuran 1000mm x 2000mm.',
                'category_id' => $categoryWipId,
                'type' => 'product',
                'product_type' => 'wip',
                'unit_id' => $unitShtId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitShtId,
                'cost_price' => 195000.00,
                'selling_price' => 0.00,
                'min_stock' => 500.00,
                'max_stock' => 5000.00,
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => false,
                'track_batch' => true,
                'attributes' => [
                    'source_coil' => 'COIL-GA-SGCC-0.8',
                    'thickness' => 0.8,
                    'length' => 2000,
                    'width' => 1000,
                    'process' => 'Shearing'
                ]
            ],

            // ==========================================
            // FINISHED GOODS - BLANKING
            // ==========================================
            [
                'sku' => 'FG-BLNK-FENDER-CR12',
                'name' => 'Blank Plate Front Fender CR 1.2mm',
                'description' => 'Baja Blank potongan presisi berbentuk komponen Fender Depan, siap dikirim ke pabrik perakitan mobil (OEM).',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 45000.00,
                'selling_price' => 62000.00,
                'min_stock' => 1000.00,
                'max_stock' => 10000.00,
                'reorder_point' => 3000.00,
                'reorder_qty' => 5000.00,
                'weight' => 2.85,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'client' => 'PT Toyota Motor Manufacturing',
                    'model' => 'Sedan / SUV X-1',
                    'thickness' => 1.2,
                    'material' => 'Cold Rolled SPCC',
                    'process' => 'Blanking'
                ]
            ],
            [
                'sku' => 'FG-BLNK-HOODIN-GA08',
                'name' => 'Blank Plate Hood Inner GA 0.8mm',
                'description' => 'Baja Blank presisi bagian dalam kap mesin dengan material Galvannealed 0.8mm tahan karat tinggi.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 38000.00,
                'selling_price' => 54000.00,
                'min_stock' => 1500.00,
                'max_stock' => 15000.00,
                'reorder_point' => 4000.00,
                'reorder_qty' => 8000.00,
                'weight' => 1.95,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'client' => 'PT Astra Daihatsu Motor',
                    'model' => 'MPV D-22',
                    'thickness' => 0.8,
                    'material' => 'Galvannealed SGCC',
                    'process' => 'Blanking'
                ]
            ],
            [
                'sku' => 'FG-BLNK-ROOF-CR16',
                'name' => 'Blank Plate Roof Center CR 1.6mm',
                'description' => 'Baja Blank potongan presisi berbentuk panel atap tengah kendaraan.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 65000.00,
                'selling_price' => 88000.00,
                'min_stock' => 800.00,
                'max_stock' => 8000.00,
                'reorder_point' => 2000.00,
                'reorder_qty' => 4000.00,
                'weight' => 4.10,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'client' => 'PT Astra Daihatsu Motor',
                    'model' => 'MPV D-22',
                    'thickness' => 1.6,
                    'material' => 'Cold Rolled SPCC',
                    'process' => 'Blanking'
                ]
            ],
            [
                'sku' => 'FG-BLNK-DISCBRK-HR26',
                'name' => 'Blank Plate Disc Brake HR 2.6mm',
                'description' => 'Baja Blank tebal melingkar untuk piringan rem cakram kendaraan bermotor.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 15000.00,
                'selling_price' => 21000.00,
                'min_stock' => 2000.00,
                'max_stock' => 20000.00,
                'reorder_point' => 5000.00,
                'reorder_qty' => 10000.00,
                'weight' => 0.98,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'client' => 'PT Toyota Motor Manufacturing',
                    'thickness' => 2.6,
                    'material' => 'Hot Rolled SPHC',
                    'process' => 'Blanking'
                ]
            ],
            [
                'sku' => 'FG-COMP-ACBRACKET-2.0',
                'name' => 'AC Compressor Bracket Plate SPHC 2.0mm',
                'description' => 'Komponen dudukan kompresor AC presisi blanking dari material SPHC 2.0mm untuk manufaktur AC.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 8500.00,
                'selling_price' => 12500.00,
                'min_stock' => 5000.00,
                'max_stock' => 50000.00,
                'reorder_point' => 10000.00,
                'reorder_qty' => 20000.00,
                'weight' => 0.45,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'client' => 'PT Panasonic Manufacturing Indonesia',
                    'thickness' => 2.0,
                    'process' => 'Blanking'
                ]
            ],

            // ==========================================
            // FINISHED GOODS - WELDING
            // ==========================================
            [
                'sku' => 'FG-TWB-DOORPANEL-01',
                'name' => 'Tailored Welded Blank Door Panel Outer',
                'description' => 'Hasil pengelasan laser presisi dari dua plat baja berbeda ketebalan (1.6mm dan 1.2mm) untuk kekuatan pintu samping optimal.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 95000.00,
                'selling_price' => 140000.00,
                'min_stock' => 500.00,
                'max_stock' => 5000.00,
                'reorder_point' => 1000.00,
                'reorder_qty' => 2000.00,
                'weight' => 5.20,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'process' => 'Welding',
                    'technology' => 'Laser Tailored Welding (TWB)',
                    'thickness_combination' => '1.6mm + 1.2mm',
                    'client' => 'PT Honda Prospect Motor'
                ]
            ],
            [
                'sku' => 'FG-TWB-SIDEPANEL-02',
                'name' => 'Laser Welded Blank Side Outer Panel',
                'description' => 'Penyatuan laser presisi plat baja GA dengan ketebalan 2.0mm dan 1.4mm untuk panel samping kendaraan.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 180000.00,
                'selling_price' => 260000.00,
                'min_stock' => 300.00,
                'max_stock' => 3000.00,
                'reorder_point' => 500.00,
                'reorder_qty' => 1000.00,
                'weight' => 8.45,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'process' => 'Welding',
                    'technology' => 'Laser Tailored Welding (TWB)',
                    'thickness_combination' => '2.0mm + 1.4mm',
                    'client' => 'PT Honda Prospect Motor'
                ]
            ],
            [
                'sku' => 'FG-TWB-PILLAR-03',
                'name' => 'Laser Welded Blank Center Pillar Reinforce',
                'description' => 'Hasil penyatuan plat High Tensile Steel (2.3mm) dan Cold Rolled SPCC (1.6mm) menggunakan laser welding untuk pilar pelindung benturan samping.',
                'category_id' => $categoryFgId,
                'type' => 'product',
                'product_type' => 'finished_good',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitKgId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 120000.00,
                'selling_price' => 175000.00,
                'min_stock' => 400.00,
                'max_stock' => 4000.00,
                'reorder_point' => 800.00,
                'reorder_qty' => 1500.00,
                'weight' => 6.10,
                'weight_unit' => 'kg',
                'is_manufactured' => true,
                'is_purchased' => false,
                'is_sold' => true,
                'track_batch' => true,
                'attributes' => [
                    'process' => 'Welding',
                    'technology' => 'Laser Tailored Welding (TWB)',
                    'thickness_combination' => '2.3mm HTS + 1.6mm SPCC',
                    'client' => 'PT Toyota Motor Manufacturing'
                ]
            ],

            // ==========================================
            // SPARE PARTS (Suku Cadang Mesin Produksi)
            // ==========================================
            [
                'sku' => 'SP-SLITKNIFE-SKD11-300',
                'name' => 'Slitting Rotary Knife SKD11 Ø300',
                'description' => 'Pisau slitting putar presisi berbahan SKD11, diameter luar 300mm. Digunakan pada mesin Slitter Line Karawang/Cikarang.',
                'category_id' => $categorySpId,
                'type' => 'consumable',
                'product_type' => 'spare_part',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitPcsId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 4500000.00,
                'selling_price' => 0.00,
                'min_stock' => 10.00,
                'max_stock' => 50.00,
                'reorder_point' => 15.00,
                'reorder_qty' => 20.00,
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'attributes' => [
                    'material' => 'SKD11 Tool Steel',
                    'outer_diameter' => 300,
                    'thickness' => 15,
                    'hardness' => 'HRC 60-62'
                ]
            ],
            [
                'sku' => 'SP-SHEARBLADE-2500',
                'name' => 'Shearing Machine Upper Blade L=2500mm',
                'description' => 'Pisau atas mesin shearing guillotine, panjang 2500mm untuk pemotongan lembaran coil.',
                'category_id' => $categorySpId,
                'type' => 'consumable',
                'product_type' => 'spare_part',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitPcsId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 18500000.00,
                'selling_price' => 0.00,
                'min_stock' => 2.00,
                'max_stock' => 6.00,
                'reorder_point' => 3.00,
                'reorder_qty' => 2.00,
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'attributes' => [
                    'length' => 2500,
                    'type' => 'Upper Blade',
                    'machine_compatibility' => 'Amada / Komatsu Shearer'
                ]
            ],
            [
                'sku' => 'SP-RUBBERING-120',
                'name' => 'Rubber Stripper Ring Spacer ID=120',
                'description' => 'Stripper ring karet spacer diameter dalam 120mm untuk pengaturan jarak pisau slitting.',
                'category_id' => $categorySpId,
                'type' => 'consumable',
                'product_type' => 'spare_part',
                'unit_id' => $unitPcsId,
                'purchase_unit_id' => $unitPcsId,
                'sales_unit_id' => $unitPcsId,
                'cost_price' => 150000.00,
                'selling_price' => 0.00,
                'min_stock' => 100.00,
                'max_stock' => 1000.00,
                'reorder_point' => 200.00,
                'reorder_qty' => 500.00,
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'attributes' => [
                    'material' => 'Urethane Rubber',
                    'inner_diameter' => 120,
                    'hardness' => 'Shore A 90'
                ]
            ],
        ];

        // 5. Insert or Update into database
        foreach ($products as $pData) {
            Product::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'sku' => $pData['sku']
                ],
                array_merge($pData, ['is_active' => true])
            );
        }
    }
}
