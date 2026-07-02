<?php

namespace App\Http\Controllers\Manufacturing;

use App\Exports\BomExport;
use App\Exports\Template\BomTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\Product;
use App\Models\Unit;
use App\Models\WorkOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class BomController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:20',
            'revision_from' => 'nullable|date',
            'revision_to' => 'nullable|date|after_or_equal:revision_from',
        ]);

        $query = Bom::with(['product', 'unit'])
            ->withCount('components')
            ->selectSub(function ($q) {
                $q->from('work_orders')
                    ->selectRaw('COALESCE(SUM(GREATEST(qty_planned - qty_produced, 0)), 0)')
                    ->whereColumn('work_orders.bom_id', 'boms.id')
                    ->whereIn('status', [WorkOrder::STATUS_CONFIRMED, WorkOrder::STATUS_IN_PROGRESS])
                    ->when(session()->has('company_id'), fn ($sq) => $sq->where('company_id', session('company_id')));
            }, 'active_remaining_qty')
            ->when(($validated['search'] ?? null), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when(($validated['status'] ?? null), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(($validated['revision_from'] ?? null), fn ($q, $dateFrom) => $q->whereDate('updated_at', '>=', $dateFrom))
            ->when(($validated['revision_to'] ?? null), fn ($q, $dateTo) => $q->whereDate('updated_at', '<=', $dateTo));

        $boms = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $boms->getCollection()->transform(function ($bom) {
            $bom->active_remaining_qty = (float) ($bom->active_remaining_qty ?? 0);
            return $bom;
        });

        return Inertia::render('Manufacturing/Boms/Index', [
            'boms' => $boms,
            'filters' => $request->only(['search', 'status', 'revision_from', 'revision_to']),
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'code', 'name']),
            'defaultMaterialWarehouseId' => $this->resolveDefaultMaterialWarehouseId(),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'archived', 'label' => 'Archived'],
            ],
        ]);
    }

    public function massCreateWorkOrders(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bom_ids' => 'required|array|min:1',
            'bom_ids.*' => 'integer|exists:boms,id',
            'qty_planned' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'required|exists:warehouses,id',
            'material_warehouse_id' => 'required|exists:warehouses,id',
            'planned_start' => 'required|date',
            'planned_end' => 'required|date|after_or_equal:planned_start',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $bomIds = array_values(array_unique($validated['bom_ids']));
        $boms = Bom::whereIn('id', $bomIds)->get(['id', 'company_id', 'product_id']);

        if (session()->has('company_id')) {
            $boms = $boms->where('company_id', (int) session('company_id'))->values();
        }

        if ($boms->isEmpty()) {
            return back()->with('error', 'Tidak ada BOM yang valid untuk dibuatkan Work Order.');
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($boms, $validated, &$created, &$skipped) {
            foreach ($boms as $bom) {
                $hasActive = WorkOrder::query()
                    ->where('bom_id', $bom->id)
                    ->whereIn('status', [WorkOrder::STATUS_CONFIRMED, WorkOrder::STATUS_IN_PROGRESS])
                    ->whereRaw('qty_planned > qty_produced')
                    ->exists();

                if ($hasActive) {
                    $skipped++;
                    continue;
                }

                $wo = WorkOrder::create([
                    'company_id' => session('company_id'),
                    'wo_number' => WorkOrder::generateWoNumber(),
                    'bom_id' => $bom->id,
                    'product_id' => $bom->product_id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'material_warehouse_id' => $validated['material_warehouse_id'],
                    'qty_planned' => $validated['qty_planned'],
                    'planned_start' => $validated['planned_start'],
                    'planned_end' => $validated['planned_end'],
                    'status' => WorkOrder::STATUS_CONFIRMED,
                    'priority' => $validated['priority'],
                    'notes' => null,
                    'created_by' => auth()->id(),
                    'production_type' => 'internal',
                    'supplier_id' => null,
                ]);

                $wo->initializeFromBom();
                $created++;
            }
        });

        $message = "Berhasil membuat {$created} Work Order (Confirmed).";
        if ($skipped > 0) {
            $message .= " {$skipped} BOM dilewati karena masih ada Active Order.";
        }

        return back()->with('success', $message);
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

    public function create(): Response
    {
        return Inertia::render('Manufacturing/Boms/Form', [
            'bom' => null,
            'products' => Product::active()->where('is_manufactured', true)->select('id','sku','name','unit_id')->orderBy('name')->get()->each->setAppends([]),
            'materials' => Product::active()->whereIn('product_type', ['raw_material', 'wip', 'spare_part', 'finished_good'])->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:boms,code',
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.0001',
            'unit_id' => 'nullable|exists:units,id',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'lead_time_days' => 'nullable|integer|min:0',
            'components' => 'required|array|min:1',
            'components.*.product_id' => 'required|exists:products,id',
            'components.*.qty' => 'required|numeric|min:0.0001',
            'components.*.unit_id' => 'nullable|exists:units,id',
            'components.*.scrap_rate' => 'nullable|numeric|min:0|max:100',
            'outputs' => 'nullable|array',
            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.qty_ratio' => 'required|numeric|min:0.0001',
            'outputs.*.unit_id' => 'required|exists:units,id',
            'outputs.*.notes' => 'nullable|string',
            'operations' => 'nullable|array',
            'operations.*.name' => 'required|string|max:255',
            'operations.*.setup_time_mins' => 'nullable|integer|min:0',
            'operations.*.processing_time_mins' => 'nullable|integer|min:0',
            'operations.*.labor_cost' => 'nullable|numeric|min:0',
            'operations.*.machine_cost' => 'nullable|numeric|min:0',
            'operations.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $bom = Bom::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'qty' => $validated['qty'],
                'unit_id' => $validated['unit_id'] ?? null,
                'version' => $validated['version'] ?? '1.0',
                'status' => 'draft',
                'description' => $validated['description'] ?? null,
                'lead_time_days' => $validated['lead_time_days'] ?? 0,
            ]);

            foreach ($validated['components'] as $index => $comp) {
                $bom->components()->create([
                    'product_id' => $comp['product_id'],
                    'qty' => $comp['qty'],
                    'unit_id' => $comp['unit_id'] ?? null,
                    'scrap_rate' => $comp['scrap_rate'] ?? 0,
                    'sequence' => $index + 1,
                ]);
            }

            if (isset($validated['outputs']) && is_array($validated['outputs'])) {
                foreach ($validated['outputs'] as $out) {
                    $bom->outputs()->create([
                        'product_id' => $out['product_id'],
                        'qty_ratio' => $out['qty_ratio'],
                        'unit_id' => $out['unit_id'],
                        'notes' => $out['notes'] ?? null,
                    ]);
                }
            }

            if (isset($validated['operations'])) {
                foreach ($validated['operations'] as $index => $op) {
                    $bom->operations()->create([
                        'name' => $op['name'],
                        'sequence' => $index + 1,
                        'setup_time_mins' => $op['setup_time_mins'] ?? 0,
                        'processing_time_mins' => $op['processing_time_mins'] ?? 0,
                        'labor_cost' => $op['labor_cost'] ?? 0,
                        'machine_cost' => $op['machine_cost'] ?? 0,
                        'description' => $op['description'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('manufacturing.boms.index')
            ->with('success', 'BOM created successfully.');
    }

    public function show(Bom $bom): Response
    {
        $bom->load(['product', 'unit', 'components.product', 'components.unit', 'outputs.product', 'outputs.unit', 'operations']);

        return Inertia::render('Manufacturing/Boms/Show', [
            'bom' => $bom,
        ]);
    }

    public function edit(Bom $bom): Response
    {
        $bom->load(['components.product', 'components.unit', 'outputs.product', 'outputs.unit', 'operations']);

        return Inertia::render('Manufacturing/Boms/Form', [
            'bom' => $bom,
            'products' => Product::active()->where('is_manufactured', true)->select('id','sku','name','unit_id')->orderBy('name')->get()->each->setAppends([]),
            'materials' => Product::active()->whereIn('product_type', ['raw_material', 'wip', 'spare_part', 'finished_good'])->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:boms,code,' . $bom->id,
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.0001',
            'unit_id' => 'nullable|exists:units,id',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'lead_time_days' => 'nullable|integer|min:0',
            'components' => 'required|array|min:1',
            'components.*.id' => 'nullable|exists:bom_components,id',
            'components.*.product_id' => 'required|exists:products,id',
            'components.*.qty' => 'required|numeric|min:0.0001',
            'components.*.unit_id' => 'nullable|exists:units,id',
            'components.*.scrap_rate' => 'nullable|numeric|min:0|max:100',
            'outputs' => 'nullable|array',
            'outputs.*.id' => 'nullable|exists:bom_outputs,id',
            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.qty_ratio' => 'required|numeric|min:0.0001',
            'outputs.*.unit_id' => 'required|exists:units,id',
            'outputs.*.notes' => 'nullable|string',
            'operations' => 'nullable|array',
            'operations.*.id' => 'nullable|exists:bom_operations,id',
            'operations.*.name' => 'required|string|max:255',
            'operations.*.setup_time_mins' => 'nullable|integer|min:0',
            'operations.*.processing_time_mins' => 'nullable|integer|min:0',
            'operations.*.labor_cost' => 'nullable|numeric|min:0',
            'operations.*.machine_cost' => 'nullable|numeric|min:0',
            'operations.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $bom) {
            $bom->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'qty' => $validated['qty'],
                'unit_id' => $validated['unit_id'] ?? null,
                'version' => $validated['version'] ?? '1.0',
                'description' => $validated['description'] ?? null,
                'lead_time_days' => $validated['lead_time_days'] ?? 0,
            ]);

            $existingIds = collect($validated['components'])->pluck('id')->filter()->all();
            $bom->components()->whereNotIn('id', $existingIds)->delete();

            foreach ($validated['components'] as $index => $comp) {
                if (isset($comp['id'])) {
                    $bom->components()->where('id', $comp['id'])->update([
                        'product_id' => $comp['product_id'],
                        'qty' => $comp['qty'],
                        'unit_id' => $comp['unit_id'] ?? null,
                        'scrap_rate' => $comp['scrap_rate'] ?? 0,
                        'sequence' => $index + 1,
                    ]);
                } else {
                    $bom->components()->create([
                        'product_id' => $comp['product_id'],
                        'qty' => $comp['qty'],
                        'unit_id' => $comp['unit_id'] ?? null,
                        'scrap_rate' => $comp['scrap_rate'] ?? 0,
                        'sequence' => $index + 1,
                    ]);
                }
            }

            $existingOutputIds = collect($validated['outputs'] ?? [])->pluck('id')->filter()->all();
            $bom->outputs()->whereNotIn('id', $existingOutputIds)->delete();

            if (isset($validated['outputs']) && is_array($validated['outputs'])) {
                foreach ($validated['outputs'] as $out) {
                    if (isset($out['id'])) {
                        $bom->outputs()->where('id', $out['id'])->update([
                            'product_id' => $out['product_id'],
                            'qty_ratio' => $out['qty_ratio'],
                            'unit_id' => $out['unit_id'],
                            'notes' => $out['notes'] ?? null,
                        ]);
                    } else {
                        $bom->outputs()->create([
                            'product_id' => $out['product_id'],
                            'qty_ratio' => $out['qty_ratio'],
                            'unit_id' => $out['unit_id'],
                            'notes' => $out['notes'] ?? null,
                        ]);
                    }
                }
            }

            $existingOpIds = collect($validated['operations'] ?? [])->pluck('id')->filter()->all();
            $bom->operations()->whereNotIn('id', $existingOpIds)->delete();

            if (isset($validated['operations'])) {
                foreach ($validated['operations'] as $index => $op) {
                    if (isset($op['id'])) {
                        $bom->operations()->where('id', $op['id'])->update([
                            'name' => $op['name'],
                            'sequence' => $index + 1,
                            'setup_time_mins' => $op['setup_time_mins'] ?? 0,
                            'processing_time_mins' => $op['processing_time_mins'] ?? 0,
                            'labor_cost' => $op['labor_cost'] ?? 0,
                            'machine_cost' => $op['machine_cost'] ?? 0,
                            'description' => $op['description'] ?? null,
                        ]);
                    } else {
                        $bom->operations()->create([
                            'name' => $op['name'],
                            'sequence' => $index + 1,
                            'setup_time_mins' => $op['setup_time_mins'] ?? 0,
                            'processing_time_mins' => $op['processing_time_mins'] ?? 0,
                            'labor_cost' => $op['labor_cost'] ?? 0,
                            'machine_cost' => $op['machine_cost'] ?? 0,
                            'description' => $op['description'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('manufacturing.boms.index')
            ->with('success', 'BOM updated successfully.');
    }

    public function export()
    {
        return Excel::download(new BomExport, 'boms_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new BomTemplateExport, 'boms_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new BomImport();

        Excel::import($import, $request->file('file'));

        $message = "Import completed: {$import->importedCount} row(s) created.";
        if ($import->skippedCount > 0) {
            $message .= " {$import->skippedCount} row(s) skipped.";
        }
        if (!empty($import->errors)) {
            $message .= ' Errors: ' . implode('; ', array_slice($import->errors, 0, 5));
        }

        return back()->with($import->importedCount > 0 ? 'success' : 'error', $message);
    }

    public function activate(Bom $bom)
    {
        $bom->update(['status' => 'active']);
        return back()->with('success', 'BOM activated.');
    }

    public function archive(Bom $bom)
    {
        $bom->update(['status' => 'archived']);
        return back()->with('success', 'BOM archived.');
    }

    public function destroy(Bom $bom)
    {
        if ($bom->workOrders()->exists()) {
            return back()->with('error', 'Cannot delete BOM with existing work orders.');
        }

        $bom->delete();

        return redirect()->route('manufacturing.boms.index')
            ->with('success', 'BOM deleted successfully.');
    }
}
