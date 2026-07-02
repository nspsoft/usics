<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\BomOutput;
use App\Models\WorkOrder;
use App\Models\WorkOrderComponent;
use App\Models\WorkOrderOutput;
use App\Models\InventoryLot;
use App\Models\ProductStock;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TestDivergentManufacturing extends Command
{
    protected $signature = 'test:divergent';
    protected $description = 'Simulate divergent manufacturing flow (Slitting)';

    public function handle()
    {
        $this->info("Starting Divergent Manufacturing Simulation...");

        DB::beginTransaction();
        try {
            // 1. Setup Basic Data
            $unitKg = Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilogram']);
            $unitPcs = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces']);
            $warehouse = Warehouse::firstOrCreate(['code' => 'W01'], ['name' => 'Main Warehouse', 'is_active' => true]);
            $employee = Employee::first();
            if (!$employee) {
                $employee = Employee::create(['first_name' => 'Test', 'last_name' => 'Operator', 'is_active' => true]);
            }
            $user = \App\Models\User::first();
            
            // Login the user for auth()->id() to work
            \Illuminate\Support\Facades\Auth::login($user);

            // 2. Create Products
            $this->info("Creating Products...");
            $motherCoilProduct = Product::firstOrCreate(
                ['sku' => 'RM-COIL-1000'],
                [
                    'name' => 'Hot Rolled Coil 1000mm x 2.5mm',
                    'type' => 'raw_material',
                    'unit_id' => $unitKg->id,
                    'cost_price' => 12000,
                    'thickness' => 2.5,
                    'width' => 1000
                ]
            );

            $babyCoil1 = Product::firstOrCreate(
                ['sku' => 'FG-COIL-600'],
                [
                    'name' => 'Slit Coil 600mm x 2.5mm',
                    'type' => 'finished_good',
                    'unit_id' => $unitKg->id,
                    'thickness' => 2.5,
                    'width' => 600
                ]
            );

            $babyCoil2 = Product::firstOrCreate(
                ['sku' => 'FG-COIL-400'],
                [
                    'name' => 'Slit Coil 400mm x 2.5mm',
                    'type' => 'finished_good',
                    'unit_id' => $unitKg->id,
                    'thickness' => 2.5,
                    'width' => 400
                ]
            );

            // 3. Setup Mother Coil Stock and Lot
            $this->info("Setting up Mother Coil Stock (20,000 kg)...");
            $motherLot = InventoryLot::create([
                'product_id' => $motherCoilProduct->id,
                'warehouse_id' => $warehouse->id,
                'coil_number' => 'M-TEST-001',
                'thickness' => 2.5,
                'width' => 1000,
                'weight' => 20000,
                'qty' => 20000,
                'status' => 'available',
            ]);

            ProductStock::firstOrCreate(
                ['product_id' => $motherCoilProduct->id, 'warehouse_id' => $warehouse->id],
                ['qty_on_hand' => 20000, 'avg_cost' => 12000]
            );

            // 4. Create BOM
            $this->info("Creating BOM...");
            $bom = Bom::create([
                'bom_number' => 'BOM-' . time(),
                'code' => 'BOM-' . time(),
                'name' => 'Slitting HRC 1000mm to 600mm & 400mm',
                'product_id' => $motherCoilProduct->id, // Main reference
                'qty_base' => 20000,
                'unit_id' => $unitKg->id,
                'cost_total' => 12000 * 20000,
                'status' => 'active',
            ]);

            BomComponent::create([
                'bom_id' => $bom->id,
                'product_id' => $motherCoilProduct->id,
                'qty' => 20000,
                'unit_id' => $unitKg->id,
            ]);

            BomOutput::create([
                'bom_id' => $bom->id,
                'product_id' => $babyCoil1->id,
                'qty_output' => 12000, // proportional output estimate
                'unit_id' => $unitKg->id,
            ]);

            BomOutput::create([
                'bom_id' => $bom->id,
                'product_id' => $babyCoil2->id,
                'qty_output' => 8000,
                'unit_id' => $unitKg->id,
            ]);

            // 5. Create Work Order
            $this->info("Creating Work Order...");
            $wo = WorkOrder::create([
                'wo_number' => 'WO-TEST-' . time(),
                'bom_id' => $bom->id,
                'product_id' => $motherCoilProduct->id,
                'qty_planned' => 20000,
                'unit_id' => $unitKg->id,
                'planned_start' => now(),
                'planned_end' => now()->addDays(1),
                'status' => 'in_progress',
                'warehouse_id' => $warehouse->id,
                'material_warehouse_id' => $warehouse->id,
                'manufacturing_type' => 'divergent',
            ]);

            // Setup WO Components and Outputs
            WorkOrderComponent::create([
                'work_order_id' => $wo->id,
                'product_id' => $motherCoilProduct->id,
                'qty_required' => 20000,
                'unit_id' => $unitKg->id,
            ]);

            WorkOrderOutput::create([
                'work_order_id' => $wo->id,
                'product_id' => $babyCoil1->id,
                'qty_planned' => 12000,
                'unit_id' => $unitKg->id,
            ]);

            WorkOrderOutput::create([
                'work_order_id' => $wo->id,
                'product_id' => $babyCoil2->id,
                'qty_planned' => 8000,
                'unit_id' => $unitKg->id,
            ]);

            // 6. Simulate Record Production Request
            $this->info("Simulating Production Recording...");
            $request = new Request([
                'production_date' => now()->format('Y-m-d'),
                'shift' => '1',
                'operator_employee_id' => $employee->id,
                'qty_good' => 19500, // Total generated weight
                'qty_rejected' => 0,
                'mother_coil_lot_id' => $motherLot->id,
                'baby_coils' => [
                    [
                        'product_id' => $babyCoil1->id,
                        'coil_number' => 'M-TEST-001-01',
                        'thickness' => 2.5,
                        'width' => 600,
                        'weight' => 11800 // actual weight
                    ],
                    [
                        'product_id' => $babyCoil2->id,
                        'coil_number' => 'M-TEST-001-02',
                        'thickness' => 2.5,
                        'width' => 400,
                        'weight' => 7700 // actual weight
                    ]
                ]
            ]);

            // Direct simulation of Controller logic
            $controller = app()->make(\App\Http\Controllers\Manufacturing\WorkOrderController::class);
            
            // To properly mock the Request injection in the controller method, 
            // it's easier to replicate the transaction block here, OR pass the request if the method is accessible.
            // But since the method requires Route Model Binding, we'll manually invoke the transaction logic here.
            
            $validated = $request->validate([
                'production_date' => 'required|date',
                'shift' => 'required|in:1,2,3',
                'operator_employee_id' => 'required|exists:hr_employees,id',
                'qty_good' => 'required|numeric|min:0',
                'qty_rejected' => 'nullable|numeric|min:0',
                'mother_coil_lot_id' => 'nullable|exists:inventory_lots,id',
                'baby_coils' => 'nullable|array',
                'baby_coils.*.product_id' => 'required|exists:products,id',
                'baby_coils.*.coil_number' => 'required|string|max:100',
                'baby_coils.*.weight' => 'required|numeric|min:0.0001',
            ]);

            // RUN LOGIC
            DB::transaction(function () use ($validated, $wo, $warehouse) {
                $wo->loadMissing(['components.product', 'components.unit', 'outputs']);
    
                $qtyGood = (float) $validated['qty_good'];
                $qtyRejected = (float) ($validated['qty_rejected'] ?? 0);
                $qtyForConsumption = $qtyGood + $qtyRejected;
    
                $entry = $wo->productionEntries()->create([
                    'production_date' => $validated['production_date'],
                    'shift' => $validated['shift'],
                    'qty_produced' => $qtyGood,
                    'qty_rejected' => $qtyRejected,
                    'operator_employee_id' => $validated['operator_employee_id'],
                    'entry_user_id' => auth()->id(),
                ]);
    
                $productionCost = 0.0;
                if ($qtyForConsumption > 0 && !empty($validated['mother_coil_lot_id'])) {
                    $motherCoil = \App\Models\InventoryLot::find($validated['mother_coil_lot_id']);
                    $comp = $wo->components()->where('product_id', $motherCoil->product_id)->first();
                    $consumeQty = $qtyForConsumption;
                    
                    if ($motherCoil->qty < $consumeQty) {
                        $consumeQty = $motherCoil->qty;
                    }
                    
                    \App\Models\MaterialConsumption::create([
                        'work_order_id' => $wo->id,
                        'work_order_component_id' => $comp->id,
                        'product_id' => $comp->product_id,
                        'warehouse_id' => $warehouse->id,
                        'qty' => $consumeQty,
                        'unit_id' => $comp->unit_id,
                        'batch_number' => $motherCoil->coil_number,
                        'consumption_date' => $validated['production_date'],
                        'consumed_by' => auth()->id(),
                    ]);
                    
                    $motherCoil->qty -= $consumeQty;
                    if ($motherCoil->qty <= 0) {
                        $motherCoil->status = 'consumed';
                    }
                    $motherCoil->save();
                    
                    $productionCost += $consumeQty * (float) ($comp->product?->cost_price ?? 0);
                }
    
                if (!empty($validated['baby_coils'])) {
                    $costPerKg = $qtyGood > 0 ? ($productionCost / $qtyGood) : 0;
                    
                    foreach ($validated['baby_coils'] as $coilData) {
                        $coilWeight = (float) $coilData['weight'];
                        
                        \App\Models\InventoryLot::create([
                            'product_id' => $coilData['product_id'],
                            'warehouse_id' => $wo->warehouse_id,
                            'coil_number' => $coilData['coil_number'],
                            'weight' => $coilWeight,
                            'qty' => $coilWeight,
                            'status' => 'available',
                        ]);
                        
                        $woOutput = $wo->outputs()->where('product_id', $coilData['product_id'])->first();
                        if ($woOutput) {
                            $woOutput->qty_produced += 1;
                            $woOutput->weight_produced += $coilWeight;
                            $woOutput->save();
                        }
    
                        $stock = ProductStock::firstOrCreate(
                            ['product_id' => $coilData['product_id'], 'warehouse_id' => $wo->warehouse_id],
                            ['qty_on_hand' => 0, 'avg_cost' => 0]
                        );
    
                        $stock->adjustStock(
                            $coilWeight,
                            $costPerKg,
                            \App\Models\StockMovement::TYPE_PRODUCTION_OUT,
                            $entry,
                            "Production Output WO #{$wo->wo_number}",
                            "PE:{$entry->id}:{$coilData['coil_number']}"
                        );
                    }
                }
                
                $entry->update(['stock_posted_at' => now()]);
            });

            // 7. Verification Output
            $this->info("====================================");
            $this->info("VERIFICATION RESULTS:");
            $this->info("====================================");
            
            // Mother Coil Check
            $updatedMotherLot = InventoryLot::find($motherLot->id);
            $this->info("Mother Coil Status: " . $updatedMotherLot->status);
            $this->info("Mother Coil Qty Remaining: " . $updatedMotherLot->qty);
            
            // Baby Coils Check
            $createdLots = InventoryLot::whereIn('coil_number', ['M-TEST-001-01', 'M-TEST-001-02'])->get();
            foreach ($createdLots as $lot) {
                $productName = $lot->product->name;
                $this->info("Created Lot: {$lot->coil_number} | {$productName} | Qty: {$lot->qty}");
            }
            
            // Stock Check
            $fg1Stock = ProductStock::where('product_id', $babyCoil1->id)->first();
            $fg2Stock = ProductStock::where('product_id', $babyCoil2->id)->first();
            $this->info("FG 600mm Stock: " . ($fg1Stock ? $fg1Stock->qty_on_hand : 0) . " kg");
            $this->info("FG 400mm Stock: " . ($fg2Stock ? $fg2Stock->qty_on_hand : 0) . " kg");

            // WO Outputs Check
            $updatedWo = WorkOrder::with('outputs.product')->find($wo->id);
            foreach ($updatedWo->outputs as $out) {
                $this->info("WO Output {$out->product->name} -> Qty Prod: {$out->qty_produced}, Weight: {$out->weight_produced} kg");
            }

            DB::rollBack(); // We rollback to keep DB clean
            $this->info("Simulation completed. DB Rolled back successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
