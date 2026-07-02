<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\QcInspection;
use App\Models\QcInspectionItem;
use App\Models\NonConformanceReport;
use App\Models\QcMasterPoint;
use App\Models\Supplier;
use App\Models\MtcDocument;
use App\Models\MtcItem;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class QcDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // 1. Ensure we have products with QC Master Points
        $products = Product::inRandomOrder()->take(5)->get();
        
        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping QC Seeding.');
            return;
        }

        foreach ($products as $product) {
            // Create Master Points if not exist
            if ($product->qcMasterPoints()->count() == 0) {
                $points = [
                    ['parameter_name' => 'Visual Appearance', 'standard_min' => 0, 'standard_max' => 0, 'unit' => 'N/A', 'method' => 'Visual'],
                    ['parameter_name' => 'Diameter', 'standard_min' => 10.0, 'standard_max' => 10.5, 'unit' => 'mm', 'method' => 'Caliper'],
                    ['parameter_name' => 'Thickness', 'standard_min' => 2.0, 'standard_max' => 2.2, 'unit' => 'mm', 'method' => 'Micrometer'],
                    ['parameter_name' => 'Hardness', 'standard_min' => 50, 'standard_max' => 60, 'unit' => 'HRC', 'method' => 'Tester'],
                ];

                foreach ($points as $point) {
                    QcMasterPoint::create([
                        'product_id' => $product->id,
                        ...$point
                    ]);
                }
            }
        }

        $inspector = User::first(); // Just pick first user
        if (!$inspector) {
             $this->command->info('No users found. Skipping QC Seeding.');
             return;
        }

        // 2. Generate Inspections (Past 30 Days)
        QcInspection::query()->delete();
        for ($i = 0; $i < 50; $i++) {
            $date = Carbon::now()->subDays(rand(0, 30));
            $product = $products->random();
            
            $status = $faker->randomElement(['pass', 'pass', 'pass', 'fail', 'conditional_pass']);
            
            $inspection = QcInspection::create([
                'inspector_id' => $inspector->id,
                'inspection_date' => $date,
                'status' => $status,
                'sample_size' => rand(1, 10),
                'notes' => $faker->sentence,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Create Inspection Items
            foreach ($product->qcMasterPoints as $mp) {
                $isPass = true;
                if ($mp->standard_min == 0 && $mp->standard_max == 0) {
                    // Visual check
                    $actual = 0;
                    if ($status === 'fail' && rand(0, 4) === 0) {
                        $isPass = false;
                    }
                } else {
                    $mean = ($mp->standard_min + $mp->standard_max) / 2;
                    $range = $mp->standard_max - $mp->standard_min;
                    
                    // Generate normal-like variance
                    $variance = (rand(-100, 100) / 100) * ($range * 0.35);
                    $actual = round($mean + $variance, 2);
                    
                    // If fail, we force it to exceed standard limits
                    if ($status === 'fail' && rand(0, 1)) {
                        $isPass = false;
                        $actual = rand(0, 1) 
                            ? round($mp->standard_max + (rand(5, 20) / 100), 2)
                            : round($mp->standard_min - (rand(5, 20) / 100), 2);
                    }
                }

                QcInspectionItem::create([
                    'qc_inspection_id' => $inspection->id,
                    'qc_master_point_id' => $mp->id,
                    'actual_value' => $actual,
                    'is_pass' => $isPass,
                    'remark' => $isPass ? 'OK' : 'Out of Spec',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            // Create NCR if fail
            if ($status === 'fail') {
                NonConformanceReport::create([
                    'qc_inspection_id' => $inspection->id,
                    'defect_type' => $faker->randomElement(['Surface Crack', 'Dimension Error', 'Material Hardness', 'Rust/Corrosion', 'Peeling']),
                    'defect_description' => $faker->sentence,
                    'root_cause' => $faker->sentence,
                    'action_plan' => $faker->paragraph,
                    'disposition' => $faker->randomElement(['Scrap', 'Rework', 'Return to Vendor']),
                    'status' => $faker->randomElement(['open', 'closed']),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // 3. Generate MTC Documents (Mill Test Certificates)
        MtcItem::query()->delete();
        MtcDocument::query()->delete();

        $suppliers = Supplier::whereIn('id', [1, 2, 3, 5])->get();
        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::take(4)->get();
        }

        // Steel coil products we will link to
        $steelProducts = Product::where('name', 'like', '%Coil%')
            ->orWhere('name', 'like', '%HRC%')
            ->orWhere('name', 'like', '%CRC%')
            ->get();

        if ($steelProducts->isEmpty()) {
            $steelProducts = Product::take(5)->get();
        }

        $commodities = ['Hot Rolled Coil', 'Cold Rolled Coil', 'Galvannealed Coil', 'Steel Plate'];
        $specs = ['SPHC', 'SPCC-SD', 'SGCC', 'API 5L BM PSL2', 'JIS G3101 SS400'];

        for ($i = 1; $i <= 18; $i++) {
            $supplier = $suppliers->random();
            $status = $faker->randomElement(['draft', 'verified', 'pushed_to_sap', 'rejected']);
            $date = Carbon::now()->subDays(rand(1, 45));
            $certNum = 'CERT-' . strtoupper(substr($supplier->name, 3, 2)) . '-' . $date->format('Y') . '-' . str_pad($i, 5, '0', STR_PAD_LEFT);
            $poNum = 'PO-' . $date->format('Y') . '-' . rand(10000, 99999);
            $orderNum = 'SO-' . rand(100000, 999999);
            $commodity = $faker->randomElement($commodities);
            $spec = $faker->randomElement($specs);

            $mtc = MtcDocument::create([
                'file_path' => 'mtc_documents/dummy_' . $i . '.pdf',
                'file_name' => $certNum . '.pdf',
                'file_type' => 'pdf',
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'certificate_number' => $certNum,
                'date_of_issue' => $date->copy()->subDays(rand(2, 7)),
                'order_no' => $orderNum,
                'po_no' => $poNum,
                'commodity' => $commodity,
                'spec_and_type' => $spec,
                'customer' => 'PT Steel Pipe Industry of Indonesia Tbk',
                'raw_ai_response' => [
                    'certificate_number' => $certNum,
                    'supplier_name' => $supplier->name,
                    'po_no' => $poNum,
                    'date_of_issue' => $date->copy()->subDays(rand(2, 7))->format('Y-m-d'),
                ],
                'status' => $status,
                'verified_by' => in_array($status, ['verified', 'pushed_to_sap']) ? $inspector->id : null,
                'verified_at' => in_array($status, ['verified', 'pushed_to_sap']) ? $date : null,
                'notes' => $status === 'rejected' ? 'Chemical analysis out of tolerance bounds.' : $faker->sentence,
                'created_by' => $inspector->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add 3-5 items (coils) per MTC document
            $itemCount = rand(3, 5);
            for ($j = 1; $j <= $itemCount; $j++) {
                $product = $steelProducts->random();
                $heatNo = 'HT' . rand(100000, 999999);
                $productNo = 'COIL-' . rand(100000, 999999);
                $size = $faker->randomElement(['2.00x1219xC', '1.20x1219xC', '0.80x1219xC', '6.02x1045xC', '7.11x1035xC']);
                $weight = rand(15000, 24000); // 15-24 tons

                // Mechanical Properties
                $yp = rand(290, 420);
                $ts = rand(410, 560);
                $el = rand(28, 42);
                $yr = round(($yp / $ts) * 100, 2);

                // Chemical Composition
                $chemicalLadle = [
                    'C' => round(rand(12, 19) / 100, 4), // 0.12 - 0.19
                    'Si' => round(rand(15, 25) / 100, 4), // 0.15 - 0.25
                    'Mn' => round(rand(70, 95) / 100, 4), // 0.70 - 0.95
                    'P' => round(rand(8, 15) / 1000, 4),  // 0.008 - 0.015
                    'S' => round(rand(4, 10) / 1000, 4),  // 0.004 - 0.010
                    'Cr' => round(rand(5, 15) / 1000, 4),
                    'Ni' => round(rand(8, 15) / 1000, 4),
                    'B' => round(rand(1, 5) / 10000, 4),
                    'Cu' => round(rand(8, 20) / 1000, 4),
                    'Mo' => round(rand(0, 5) / 1000, 4),
                    'Nb' => 0.0000,
                    'Ti' => round(rand(1, 3) / 1000, 4),
                    'V' => round(rand(1, 3) / 1000, 4),
                ];
                // Calculate CEQ: C + Mn/6 + (Cr+Mo+V)/5 + (Ni+Cu)/15
                $ceq = $chemicalLadle['C'] + ($chemicalLadle['Mn'] / 6) + (($chemicalLadle['Cr'] + $chemicalLadle['Mo'] + $chemicalLadle['V']) / 5) + (($chemicalLadle['Ni'] + $chemicalLadle['Cu']) / 15);
                $chemicalLadle['ceq'] = round($ceq, 4);

                $chemicalProduct = $chemicalLadle;
                $chemicalProduct['C'] = round($chemicalProduct['C'] + (rand(-10, 10) / 1000), 4);
                $chemicalProduct['Mn'] = round($chemicalProduct['Mn'] + (rand(-20, 20) / 1000), 4);

                $compliance = $status === 'rejected' ? 'fail' : 'pass';
                $complianceNotes = $compliance === 'fail' ? 'Carbon content exceeds maximum specification limit of 0.18%.' : 'All values conform to specification standards.';

                MtcItem::create([
                    'mtc_document_id' => $mtc->id,
                    'product_id' => $product->id,
                    'product_no' => $productNo,
                    'heat_no' => $heatNo,
                    'size' => $size,
                    'quantity' => 1,
                    'weight_kg' => $weight,
                    'position' => $faker->randomElement(['T', 'M', 'B', null]),
                    'yp_mpa' => $yp,
                    'ts_mpa' => $ts,
                    'el_percent' => $el,
                    'yr_percent' => $yr,
                    'bend_test' => 'Good',
                    'impact_test_data' => null,
                    'chemical_ladle' => $chemicalLadle,
                    'chemical_product' => $chemicalProduct,
                    'division' => null,
                    'compliance_status' => $compliance,
                    'compliance_notes' => $complianceNotes,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
