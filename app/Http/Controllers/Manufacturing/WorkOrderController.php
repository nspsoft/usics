<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Exports\Template\WorkOrderTemplateExport;
use App\Imports\WorkOrdersImport;
use App\Models\Bom;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductionEntry;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\MaterialConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WorkOrder::with(['product', 'bom', 'warehouse'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('wo_number', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->priority, function ($q, $priority) {
                $q->where('priority', $priority);
            })
            ->when($request->production_type, function ($q, $type) {
                $q->where('production_type', $type);
            });

        $workOrders = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
            
        // Add computed properties
        $workOrders->getCollection()->transform(function ($wo) {
            $wo->progress_percent = $wo->progress_percent;
            $wo->remaining_qty = $wo->remaining_qty;
            return $wo;
        });

        return Inertia::render('Manufacturing/WorkOrders/Index', [
            'workOrders' => $workOrders,
            'filters' => $request->only(['search', 'status', 'priority', 'production_type']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
            'priorities' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'urgent', 'label' => 'Urgent'],
            ],
            'productionTypes' => [
                ['value' => 'internal', 'label' => 'Internal'],
                ['value' => 'subcontract', 'label' => 'Subcontract'],
            ],
        ]);
    }

    public function template()
    {
        return Excel::download(new WorkOrderTemplateExport(), 'work_orders_template.xlsx');
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $import = new WorkOrdersImport();
        Excel::import($import, $validated['file']);

        $message = "Import selesai: {$import->importedCount} WO dibuat (status Confirmed).";
        if ($import->skippedCount > 0) {
            $message .= " {$import->skippedCount} baris dilewati.";
        }
        if (!empty($import->errors)) {
            $message .= ' Errors: ' . implode('; ', array_slice($import->errors, 0, 5));
        }

        $hasSuccess = ($import->importedCount > 0);
        return back()->with($hasSuccess ? 'success' : 'error', $message);
    }

    public function create(): Response
    {
        return Inertia::render('Manufacturing/WorkOrders/Form', [
            'workOrder' => null,
            'woNumber' => WorkOrder::generateWoNumber(),
            'boms' => Bom::active()->with('product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'defaultMaterialWarehouseId' => $this->resolveDefaultMaterialWarehouseId(),
            'suppliers' => Supplier::subcontractors()
                ->where('company_id', session('company_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wo_number' => 'required|string|max:30|unique:work_orders,wo_number',
            'bom_id' => 'required|exists:boms,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'material_warehouse_id' => 'required|exists:warehouses,id',
            'qty_planned' => 'required|numeric|min:0.0001',
            'planned_start' => 'required|date',
            'planned_end' => 'required|date|after_or_equal:planned_start',
            'priority' => 'required|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'production_type' => 'required|in:internal,subcontract',
            'supplier_id' => [
                'nullable',
                'required_if:production_type,subcontract',
                Rule::exists('suppliers', 'id')->where(function ($q) {
                    $q->where('company_id', session('company_id'))
                        ->where('is_active', true)
                        ->whereNotNull('subcontract_warehouse_id');
                }),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $bom = Bom::find($validated['bom_id']);
            
            $wo = WorkOrder::create([
                'company_id' => session('company_id'),
                'wo_number' => $validated['wo_number'],
                'bom_id' => $validated['bom_id'],
                'product_id' => $bom->product_id,
                'warehouse_id' => $validated['warehouse_id'],
                'material_warehouse_id' => $validated['material_warehouse_id'],
                'qty_planned' => $validated['qty_planned'],
                'planned_start' => $validated['planned_start'],
                'planned_end' => $validated['planned_end'],
                'status' => 'draft',
                'priority' => $validated['priority'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'production_type' => $validated['production_type'],
                'supplier_id' => $validated['supplier_id'],
            ]);

            // Initialize components from BOM
            $wo->initializeFromBom();

            if ($wo->production_type === 'subcontract') {
                $this->generateSubcontractOrder($wo);
            }
        });

        return redirect()->route('manufacturing.work-orders.index')
            ->with('success', 'Work Order created successfully.');
    }

    public function show(WorkOrder $workOrder): Response
    {
        $workOrder->load(['product', 'bom', 'warehouse', 'supplier', 'components.product', 'productionEntries.operatorEmployee', 'productionEntries.entryUser', 'productionEntries.producedBy', 'materialConsumptions.product', 'subcontractOrders']);

        $subcontractGrReceipts = [];
        if ($workOrder->production_type === 'subcontract') {
            $subcontractOrder = $workOrder->subcontractOrders
                ?->sortByDesc('id')
                ->first();

            if ($subcontractOrder?->purchase_order_id) {
                $fgProductId = $workOrder->product_id;

                $subcontractGrReceipts = GoodsReceipt::query()
                    ->where('purchase_order_id', $subcontractOrder->purchase_order_id)
                    ->where('status', GoodsReceipt::STATUS_COMPLETED)
                    ->with(['items'])
                    ->orderByDesc('receipt_date')
                    ->get()
                    ->map(function ($gr) use ($fgProductId) {
                        $qty = $fgProductId
                            ? (float) $gr->items->where('product_id', $fgProductId)->sum('qty_received')
                            : (float) $gr->items->sum('qty_received');

                        return [
                            'id' => $gr->id,
                            'grn_number' => $gr->grn_number,
                            'receipt_date' => $gr->receipt_date,
                            'status' => $gr->status,
                            'qty' => $qty,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        return Inertia::render('Manufacturing/WorkOrders/Show', [
            'workOrder' => $workOrder,
            'shiftOptions' => ProductionEntry::getShiftOptions(),
            'defectCategories' => ProductionEntry::getDefectCategories(),
            'operators' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'nik', 'full_name']),
            'subcontractGrReceipts' => $subcontractGrReceipts,
        ]);
    }

    public function confirm(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'draft') {
            return back()->with('error', 'Only draft work orders can be confirmed.');
        }

        $workOrder->update(['status' => 'confirmed']);

        return back()->with('success', 'Work Order confirmed.');
    }

    public function revertToDraft(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed work orders can be reverted to draft.');
        }

        $workOrder->update(['status' => 'draft']);

        return back()->with('success', 'Work Order reverted to draft for revision.');
    }

    public function start(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed work orders can be started.');
        }

        $workOrder->start();

        return back()->with('success', 'Production started.');
    }

    public function bulkStart(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique($validated['ids']));

        $workOrders = WorkOrder::whereIn('id', $ids)->get(['id', 'status']);
        $eligibleIds = $workOrders->where('status', WorkOrder::STATUS_CONFIRMED)->pluck('id')->values();
        $skipped = $workOrders->count() - $eligibleIds->count();

        if ($eligibleIds->isEmpty()) {
            return back()->with('error', 'Tidak ada Work Order berstatus Confirmed yang dipilih.');
        }

        DB::transaction(function () use ($eligibleIds) {
            WorkOrder::whereIn('id', $eligibleIds)->update([
                'status' => WorkOrder::STATUS_IN_PROGRESS,
                'actual_start' => now(),
            ]);
        });

        $message = "Started {$eligibleIds->count()} work orders.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} karena status bukan Confirmed.";
        }

        return back()->with('success', $message);
    }

    public function bulkStartFiltered(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'production_type' => 'nullable|in:internal,subcontract',
        ]);

        $query = WorkOrder::query()
            ->where('status', WorkOrder::STATUS_CONFIRMED)
            ->when(($validated['search'] ?? null), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('wo_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(($validated['priority'] ?? null), fn ($q, $priority) => $q->where('priority', $priority))
            ->when(($validated['production_type'] ?? null), fn ($q, $type) => $q->where('production_type', $type));

        $count = (clone $query)->count();
        if ($count <= 0) {
            return back()->with('error', 'Tidak ada Work Order berstatus Confirmed sesuai filter.');
        }

        DB::transaction(function () use ($query) {
            $query->update([
                'status' => WorkOrder::STATUS_IN_PROGRESS,
                'actual_start' => now(),
            ]);
        });

        return back()->with('success', "Started {$count} work orders (filtered).");
    }

    public function complete(WorkOrder $workOrder)
    {
        if ($workOrder->production_type === 'subcontract') {
            return back()->with('error', 'Work Order subcontract diproses lewat GR PO (Purchasing). Menu complete dinonaktifkan untuk menghindari double posting stok.');
        }

        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress work orders can be completed.');
        }

        $workOrder->complete();

        return back()->with('success', 'Work Order completed. Finished goods added to stock.');
    }

    public function cancel(WorkOrder $workOrder)
    {
        if (in_array($workOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This work order cannot be cancelled.');
        }

        $workOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Work Order cancelled.');
    }

    public function reopen(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'cancelled') {
            return back()->with('error', 'Only cancelled work orders can be reopened.');
        }

        $workOrder->update(['status' => 'draft']);

        if ((float) $workOrder->qty_produced > 0 || $workOrder->productionEntries()->exists()) {
            return back()->with('warning', 'Work Order reopened to draft. Production entries already exist; please review before confirming.');
        }

        return back()->with('success', 'Work Order reopened to draft.');
    }

    public function destroy(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'draft') {
            return back()->with('error', 'Only draft work orders can be deleted.');
        }

        $workOrder->delete();

        return redirect()->route('manufacturing.work-orders.index')
            ->with('success', 'Work Order deleted successfully.');
    }

    public function edit(WorkOrder $workOrder): Response
    {
        $suppliers = Supplier::subcontractors()
            ->where('company_id', session('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
        if ($workOrder->supplier_id && !$suppliers->contains('id', $workOrder->supplier_id)) {
            $selected = Supplier::query()->whereKey($workOrder->supplier_id)->get(['id', 'name']);
            $suppliers = $selected->concat($suppliers)->unique('id')->values();
        }

        return Inertia::render('Manufacturing/WorkOrders/Form', [
            'workOrder' => $workOrder,
            'woNumber' => $workOrder->wo_number,
            'boms' => Bom::active()->with('product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'defaultMaterialWarehouseId' => $this->resolveDefaultMaterialWarehouseId(),
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'wo_number' => 'required|string|max:30|unique:work_orders,wo_number,' . $workOrder->id,
            'bom_id' => 'required|exists:boms,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'material_warehouse_id' => 'required|exists:warehouses,id',
            'qty_planned' => 'required|numeric|min:0.0001',
            'planned_start' => 'required|date',
            'planned_end' => 'required|date|after_or_equal:planned_start',
            'priority' => 'required|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'production_type' => 'required|in:internal,subcontract',
            'supplier_id' => [
                'nullable',
                'required_if:production_type,subcontract',
                Rule::exists('suppliers', 'id')->where(function ($q) {
                    $q->where('company_id', session('company_id'))
                        ->where('is_active', true)
                        ->whereNotNull('subcontract_warehouse_id');
                }),
            ],
        ]);

        DB::transaction(function () use ($validated, $workOrder) {
            $workOrder->update($validated);

            if ($workOrder->production_type === 'subcontract') {
                $this->generateSubcontractOrder($workOrder);
            }
        });

        return redirect()->route('manufacturing.work-orders.index')
            ->with('success', 'Work Order updated successfully.');
    }

    private function resolveDefaultMaterialWarehouseId(): ?int
    {
        $warehouse = Warehouse::query()
            ->where('code', 'WH-RM')
            ->orWhereRaw('LOWER(name) LIKE ?', ['%raw material%'])
            ->orderByRaw("CASE WHEN code = 'WH-RM' THEN 0 ELSE 1 END")
            ->first();

        return $warehouse?->id;
    }

    public function recordProductionForm(WorkOrder $workOrder): Response
    {
        if ($workOrder->production_type === 'subcontract') {
            return redirect()->route('manufacturing.work-orders.show', $workOrder->id)
                ->with('error', 'Work Order subcontract diproses lewat GR PO (Purchasing). Menu input produksi dinonaktifkan untuk menghindari double input.');
        }

        if ($workOrder->status !== 'in_progress') {
            return redirect()->route('manufacturing.work-orders.show', $workOrder->id)
                ->with('error', 'Production can only be recorded for in-progress work orders.');
        }

        $workOrder->load(['product', 'bom']);

        return Inertia::render('Manufacturing/WorkOrders/RecordProduction', [
            'workOrder' => $workOrder,
            'shiftOptions' => ProductionEntry::getShiftOptions(),
            'machineOptions' => ProductionEntry::getMachineOptions(),
            'defectCategories' => ProductionEntry::getDefectCategories(),
            'operators' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'nik', 'full_name']),
            'defaultOperatorEmployeeId' => auth()->user()?->employee?->id,
        ]);
    }

    public function productionEntryIndex(Request $request): Response
    {
        $query = WorkOrder::with(['product', 'bom', 'components'])
            ->where('status', 'in_progress')
            ->where('production_type', '!=', 'subcontract')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('wo_number', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%")
                             ->orWhere('sku', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('planned_start', 'asc');

        $workOrders = $query->paginate(9)->withQueryString();
        
        // Transform data for frontend
        $workOrders->getCollection()->transform(function ($wo) {
            $planned = (float) $wo->qty_planned;
            $produced = (float) $wo->qty_produced;
            $percent = $planned > 0 ? min(100, ($produced / $planned) * 100) : 0;
            
            return [
                'id' => $wo->id,
                'wo_number' => $wo->wo_number,
                'product_name' => $wo->product->name ?? 'Unknown Product',
                'product_sku' => $wo->product->sku ?? '-',
                'qty_planned' => $planned,
                'qty_produced' => $produced,
                'remaining' => max(0, $planned - $produced),
                'percent' => $percent,
                'planned_start' => $wo->planned_start,
            ];
        });

        return Inertia::render('Manufacturing/WorkOrders/ProductionEntryIndex', [
            'workOrders' => $workOrders,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Record production output
     */
    public function recordProduction(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->production_type === 'subcontract') {
            return back()->with('error', 'Work Order subcontract diproses lewat GR PO (Purchasing). Menu input produksi dinonaktifkan untuk menghindari double input.');
        }

        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress work orders can record production.');
        }

        $materialWarehouseId = $workOrder->material_warehouse_id ?? $this->resolveDefaultMaterialWarehouseId();
        if (!$materialWarehouseId) {
            return back()->withErrors([
                'material_warehouse_id' => 'Material Warehouse belum diset. Silakan set Material Warehouse (Raw Material) di Work Order.',
            ]);
        }

        $validated = $request->validate([
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
        ]);

        if (!empty($validated['client_request_id'])) {
            $existing = ProductionEntry::query()
                ->where('client_request_id', $validated['client_request_id'])
                ->first();

            if ($existing) {
                return back()->with('success', 'Laporan produksi sudah tersimpan.');
            }
        }

        $duplicate = $workOrder->productionEntries()
            ->whereDate('production_date', $validated['production_date'])
            ->where('shift', $validated['shift'])
            ->where('operator_employee_id', $validated['operator_employee_id'])
            ->when(
                array_key_exists('start_time', $validated) && $validated['start_time'] !== null && $validated['start_time'] !== '',
                fn ($q) => $q->where('start_time', $validated['start_time']),
                fn ($q) => $q->whereNull('start_time')
            )
            ->when(
                array_key_exists('end_time', $validated) && $validated['end_time'] !== null && $validated['end_time'] !== '',
                fn ($q) => $q->where('end_time', $validated['end_time']),
                fn ($q) => $q->whereNull('end_time')
            )
            ->exists();

        if ($duplicate) {
            return back()->with('warning', 'Laporan produksi untuk operator/shift/jam yang sama sudah ada. Jika ini batch baru, silakan ubah jam produksi.');
        }

        // Over-production safeguard: Allow only up to 20% overrun of the total planning
        $tolerance = 1.2; 
        $maxAllowed = $workOrder->qty_planned * $tolerance;
        $currentProduced = (float) $workOrder->qty_produced;
        $newTotal = $currentProduced + (float) $validated['qty_good'];

        if ($newTotal > $maxAllowed) {
            return back()->withErrors([
                'qty_good' => "Jumlah produksi terlalu besar (total " . number_format($newTotal) . " dari rencana " . number_format($workOrder->qty_planned) . "). Maksimal toleransi adalah 20%."
            ])->withInput();
        }

        DB::transaction(function () use ($validated, $workOrder, $materialWarehouseId) {
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
        });

        return back()->with('success', 'Production output recorded successfully.');
    }

    private function generateSubcontractOrder(WorkOrder $wo)
    {
        // Don't duplicate if already exists
        if ($wo->subcontractOrders()->exists()) {
            return;
        }

        $orderNumber = 'SCO-' . date('Ymd') . '-' . str_pad($wo->id, 4, '0', STR_PAD_LEFT);

        $wo->subcontractOrders()->create([
            'supplier_id' => $wo->supplier_id,
            'order_number' => $orderNumber,
            'status' => 'draft',
            'service_fee' => 0,
        ]);
    }

    public function print(WorkOrder $workOrder)
    {
        return view('print.work-order', [
            'workOrder' => $workOrder->load(['product.unit', 'bom', 'warehouse', 'supplier', 'createdBy', 'components.product.unit', 'components.unit', 'productionEntries.operatorEmployee', 'productionEntries.entryUser', 'productionEntries.producedBy', 'materialConsumptions.product.unit'])
        ]);
    }

    public function publicValidate($id)
    {
        $workOrder = WorkOrder::with(['product', 'warehouse', 'supplier'])
            ->findOrFail($id);

        return view('print.public-work-order-validation', [
            'workOrder' => $workOrder
        ]);
    }
}
