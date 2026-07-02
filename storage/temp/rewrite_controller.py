import re

with open('c:\\laragon\\www\\USICS\\app\\Http\\Controllers\\Manufacturing\\WorkOrderController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace validation rules
old_validation = """        $validated = $request->validate([
            'production_date' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'operator_employee_id' => 'required|exists:hr_employees,id',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'machine_line' => 'nullable|string|max:100',
            'qty_good' => 'required|numeric|min:0',
            'qty_rejected' => 'nullable|numeric|min:0',
            'defect_category' => 'nullable|string|max:50',
            'downtime_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'client_request_id' => 'nullable|uuid',
        ]);"""

new_validation = """        $validated = $request->validate([
            'production_date' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'operator_employee_id' => 'required|exists:hr_employees,id',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'machine_line' => 'nullable|string|max:100',
            'qty_good' => 'required|numeric|min:0',
            'qty_rejected' => 'nullable|numeric|min:0',
            'defect_category' => 'nullable|string|max:50',
            'downtime_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'client_request_id' => 'nullable|uuid',
            'mother_coil_lot_id' => 'nullable|exists:inventory_lots,id',
            'baby_coils' => 'nullable|array',
            'baby_coils.*.product_id' => 'required|exists:products,id',
            'baby_coils.*.coil_number' => 'required|string|max:100',
            'baby_coils.*.thickness' => 'nullable|numeric|min:0',
            'baby_coils.*.width' => 'nullable|numeric|min:0',
            'baby_coils.*.length' => 'nullable|numeric|min:0',
            'baby_coils.*.weight' => 'required|numeric|min:0.0001',
        ]);"""

content = content.replace(old_validation, new_validation)

# Replace transaction logic
old_transaction = """        DB::transaction(function () use ($validated, $workOrder, $materialWarehouseId) {
            $workOrder->loadMissing(['components.product', 'components.unit']);

            $qtyGood = (float) $validated['qty_good'];
            $qtyRejected = (float) ($validated['qty_rejected'] ?? 0);
            $qtyForConsumption = $qtyGood + $qtyRejected;

            $entry = $workOrder->productionEntries()->create([
                'production_date' => $validated['production_date'],
                'shift' => $validated['shift'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'machine_line' => $validated['machine_line'],
                'qty_produced' => $validated['qty_good'],
                'qty_rejected' => $validated['qty_rejected'] ?? 0,
                'defect_category' => $validated['defect_category'],
                'downtime_minutes' => $validated['downtime_minutes'] ?? 0,
                'notes' => $validated['notes'],
                'operator_employee_id' => $validated['operator_employee_id'],
                'entry_user_id' => auth()->id(),
                'client_request_id' => $validated['client_request_id'] ?? null,
            ]);

            $productionCost = 0.0;
            if ($qtyForConsumption > 0) {
                foreach ($workOrder->components as $comp) {
                    $qtyPerUnit = ((float) ($workOrder->qty_planned ?? 0)) > 0
                        ? ((float) ($comp->qty_required ?? 0) / (float) $workOrder->qty_planned)
                        : 0;

                    $consumeQty = round($qtyPerUnit * $qtyForConsumption, 4);
                    if ($consumeQty <= 0) {
                        continue;
                    }

                    MaterialConsumption::create([
                        'work_order_id' => $workOrder->id,
                        'work_order_component_id' => $comp->id,
                        'product_id' => $comp->product_id,
                        'warehouse_id' => $materialWarehouseId,
                        'location_id' => null,
                        'qty' => $consumeQty,
                        'unit_id' => $comp->unit_id,
                        'batch_number' => null,
                        'consumption_date' => $validated['production_date'],
                        'consumed_by' => auth()->id(),
                    ]);

                    $productionCost += $consumeQty * (float) ($comp->product?->cost_price ?? 0);
                }
            }

            if ($qtyGood > 0) {
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $workOrder->product_id,
                        'warehouse_id' => $workOrder->warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $stock->adjustStock(
                    $qtyGood,
                    $qtyGood > 0 ? ($productionCost / $qtyGood) : null,
                    StockMovement::TYPE_PRODUCTION_OUT,
                    $entry,
                    "Production Output WO #{$workOrder->wo_number}",
                    "PE:{$entry->id}:FG"
                );
            }

            $entry->update([
                'stock_posted_at' => now(),
            ]);
        });"""

new_transaction = """        DB::transaction(function () use ($validated, $workOrder, $materialWarehouseId) {
            $workOrder->loadMissing(['components.product', 'components.unit', 'outputs']);

            $qtyGood = (float) $validated['qty_good'];
            $qtyRejected = (float) ($validated['qty_rejected'] ?? 0);
            $qtyForConsumption = $qtyGood + $qtyRejected;

            $entry = $workOrder->productionEntries()->create([
                'production_date' => $validated['production_date'],
                'shift' => $validated['shift'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'machine_line' => $validated['machine_line'],
                'qty_produced' => $qtyGood,
                'qty_rejected' => $qtyRejected,
                'defect_category' => $validated['defect_category'],
                'downtime_minutes' => $validated['downtime_minutes'] ?? 0,
                'notes' => $validated['notes'],
                'operator_employee_id' => $validated['operator_employee_id'],
                'entry_user_id' => auth()->id(),
                'client_request_id' => $validated['client_request_id'] ?? null,
            ]);

            $productionCost = 0.0;
            if ($qtyForConsumption > 0) {
                if (!empty($validated['mother_coil_lot_id'])) {
                    // Consume specific Mother Coil
                    $motherCoil = \App\Models\InventoryLot::find($validated['mother_coil_lot_id']);
                    if ($motherCoil) {
                        $comp = $workOrder->components()->where('product_id', $motherCoil->product_id)->first();
                        if ($comp) {
                            $consumeQty = $qtyForConsumption;
                            if ($motherCoil->qty < $consumeQty) {
                                $consumeQty = $motherCoil->qty;
                            }
                            
                            if ($consumeQty > 0) {
                                MaterialConsumption::create([
                                    'work_order_id' => $workOrder->id,
                                    'work_order_component_id' => $comp->id,
                                    'product_id' => $comp->product_id,
                                    'warehouse_id' => $materialWarehouseId,
                                    'location_id' => null,
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
                        }
                    }
                } else {
                    // Backflush proportionally
                    foreach ($workOrder->components as $comp) {
                        $qtyPerUnit = ((float) ($workOrder->qty_planned ?? 0)) > 0
                            ? ((float) ($comp->qty_required ?? 0) / (float) $workOrder->qty_planned)
                            : 0;

                        $consumeQty = round($qtyPerUnit * $qtyForConsumption, 4);
                        if ($consumeQty <= 0) {
                            continue;
                        }

                        MaterialConsumption::create([
                            'work_order_id' => $workOrder->id,
                            'work_order_component_id' => $comp->id,
                            'product_id' => $comp->product_id,
                            'warehouse_id' => $materialWarehouseId,
                            'location_id' => null,
                            'qty' => $consumeQty,
                            'unit_id' => $comp->unit_id,
                            'batch_number' => null,
                            'consumption_date' => $validated['production_date'],
                            'consumed_by' => auth()->id(),
                        ]);

                        $productionCost += $consumeQty * (float) ($comp->product?->cost_price ?? 0);
                    }
                }
            }

            if (!empty($validated['baby_coils'])) {
                // Divergent processing: Process each baby coil
                $costPerKg = $qtyGood > 0 ? ($productionCost / $qtyGood) : 0;
                
                foreach ($validated['baby_coils'] as $coilData) {
                    $coilWeight = (float) $coilData['weight'];
                    
                    // Create Inventory Lot for Baby Coil
                    \App\Models\InventoryLot::create([
                        'product_id' => $coilData['product_id'],
                        'warehouse_id' => $workOrder->warehouse_id,
                        'coil_number' => $coilData['coil_number'],
                        'thickness' => $coilData['thickness'] ?? null,
                        'width' => $coilData['width'] ?? null,
                        'length' => $coilData['length'] ?? null,
                        'weight' => $coilWeight,
                        'qty' => $coilWeight,
                        'status' => 'available',
                        'notes' => "Baby coil from WO #{$workOrder->wo_number}",
                    ]);
                    
                    // Update Output stats
                    $woOutput = $workOrder->outputs()->where('product_id', $coilData['product_id'])->first();
                    if ($woOutput) {
                        $woOutput->qty_produced += 1;
                        $woOutput->weight_produced += $coilWeight;
                        $woOutput->save();
                    }

                    // Adjust Stock
                    $stock = ProductStock::firstOrCreate(
                        [
                            'product_id' => $coilData['product_id'],
                            'warehouse_id' => $workOrder->warehouse_id,
                        ],
                        [
                            'qty_on_hand' => 0,
                            'qty_reserved' => 0,
                            'qty_incoming' => 0,
                            'qty_outgoing' => 0,
                            'avg_cost' => 0,
                        ]
                    );

                    $stock->adjustStock(
                        $coilWeight,
                        $costPerKg,
                        StockMovement::TYPE_PRODUCTION_OUT,
                        $entry,
                        "Production Output WO #{$workOrder->wo_number} (Coil: {$coilData['coil_number']})",
                        "PE:{$entry->id}:{$coilData['coil_number']}"
                    );
                }
            } elseif ($qtyGood > 0) {
                // Traditional Convergent processing
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $workOrder->product_id,
                        'warehouse_id' => $workOrder->warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $stock->adjustStock(
                    $qtyGood,
                    $qtyGood > 0 ? ($productionCost / $qtyGood) : null,
                    StockMovement::TYPE_PRODUCTION_OUT,
                    $entry,
                    "Production Output WO #{$workOrder->wo_number}",
                    "PE:{$entry->id}:FG"
                );
            }

            $entry->update([
                'stock_posted_at' => now(),
            ]);
        });"""

content = content.replace(old_transaction, new_transaction)

with open('c:\\laragon\\www\\USICS\\app\\Http\\Controllers\\Manufacturing\\WorkOrderController.php', 'w', encoding='utf-8') as f:
    f.write(content)
