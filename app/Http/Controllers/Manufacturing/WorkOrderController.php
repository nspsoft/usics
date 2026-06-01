<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Exports\Template\WorkOrderTemplateExport;
use App\Imports\WorkOrdersImport;
use App\Models\Bom;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wo_number' => 'required|string|max:30|unique:work_orders,wo_number',
            'bom_id' => 'required|exists:boms,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty_planned' => 'required|numeric|min:0.0001',
            'planned_start' => 'required|date',
            'planned_end' => 'required|date|after_or_equal:planned_start',
            'priority' => 'required|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'production_type' => 'required|in:internal,subcontract',
            'supplier_id' => 'required_if:production_type,subcontract|nullable|exists:suppliers,id',
        ]);

        DB::transaction(function () use ($validated) {
            $bom = Bom::find($validated['bom_id']);
            
            $wo = WorkOrder::create([
                'company_id' => session('company_id'),
                'wo_number' => $validated['wo_number'],
                'bom_id' => $validated['bom_id'],
                'product_id' => $bom->product_id,
                'warehouse_id' => $validated['warehouse_id'],
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

        return Inertia::render('Manufacturing/WorkOrders/Show', [
            'workOrder' => $workOrder,
            'shiftOptions' => ProductionEntry::getShiftOptions(),
            'defectCategories' => ProductionEntry::getDefectCategories(),
            'operators' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'nik', 'full_name']),
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

    public function complete(WorkOrder $workOrder)
    {
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
        return Inertia::render('Manufacturing/WorkOrders/Form', [
            'workOrder' => $workOrder,
            'woNumber' => $workOrder->wo_number,
            'boms' => Bom::active()->with('product')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'wo_number' => 'required|string|max:30|unique:work_orders,wo_number,' . $workOrder->id,
            'bom_id' => 'required|exists:boms,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty_planned' => 'required|numeric|min:0.0001',
            'planned_start' => 'required|date',
            'planned_end' => 'required|date|after_or_equal:planned_start',
            'priority' => 'required|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'production_type' => 'required|in:internal,subcontract',
            'supplier_id' => 'required_if:production_type,subcontract|nullable|exists:suppliers,id',
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

    public function recordProductionForm(WorkOrder $workOrder): Response
    {
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
        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress work orders can record production.');
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
        ]);

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

        DB::transaction(function () use ($validated, $workOrder) {
            // Create production entry
            $workOrder->productionEntries()->create([
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
            ]);

            // Work order totals are updated by model event in ProductionEntry
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
            'workOrder' => $workOrder->load(['product.unit', 'bom', 'warehouse', 'supplier', 'components.product.unit', 'productionEntries.operatorEmployee', 'productionEntries.entryUser', 'productionEntries.producedBy', 'materialConsumptions.product.unit'])
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
