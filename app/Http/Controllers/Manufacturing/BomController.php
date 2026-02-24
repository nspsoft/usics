<?php

namespace App\Http\Controllers\Manufacturing;

use App\Exports\BomExport;
use App\Exports\Template\BomTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class BomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Bom::with(['product', 'unit'])
            ->withCount('components')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $boms = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Manufacturing/Boms/Index', [
            'boms' => $boms,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'archived', 'label' => 'Archived'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Manufacturing/Boms/Form', [
            'bom' => null,
            'products' => Product::active()->where('is_manufactured', true)->orderBy('name')->get(),
            'materials' => Product::active()->whereIn('product_type', ['raw_material', 'wip', 'spare_part'])->orderBy('name')->get(),
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
        $bom->load(['product', 'unit', 'components.product', 'components.unit', 'operations']);

        return Inertia::render('Manufacturing/Boms/Show', [
            'bom' => $bom,
        ]);
    }

    public function edit(Bom $bom): Response
    {
        $bom->load(['components.product', 'components.unit', 'operations']);

        return Inertia::render('Manufacturing/Boms/Form', [
            'bom' => $bom,
            'products' => Product::active()->where('is_manufactured', true)->orderBy('name')->get(),
            'materials' => Product::active()->whereIn('product_type', ['raw_material', 'wip', 'spare_part'])->orderBy('name')->get(),
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
