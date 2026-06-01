<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseArea;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseAreaController extends Controller
{
    private function makeKey(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name);
        $key = strtolower((string) $name);
        return substr($key, 0, 120);
    }

    public function index(Request $request): Response
    {
        $query = WarehouseArea::query()
            ->with(['warehouse:id,name'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when($request->warehouse_id, function ($q, $warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });

        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        if ($sort === 'warehouse_name') {
            $query->join('warehouses', 'inv_warehouse_areas.warehouse_id', '=', 'warehouses.id')
                ->orderBy('warehouses.name', $direction)
                ->select('inv_warehouse_areas.*');
        } elseif (in_array($sort, ['name', 'is_active', 'created_at'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $areas = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/WarehouseAreas/Index', [
            'areas' => $areas,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'warehouse_id', 'sort', 'direction']),
        ]);
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $areas = WarehouseArea::query()
            ->where('warehouse_id', $validated['warehouse_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inv_warehouse_areas', 'name_key')
                    ->where('warehouse_id', $request->input('warehouse_id'))
            ],
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['name_key'] = $this->makeKey($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        WarehouseArea::create($validated);

        return back()->with('success', 'Warehouse area created successfully.');
    }

    public function update(Request $request, WarehouseArea $warehouseArea)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inv_warehouse_areas', 'name_key')
                    ->where('warehouse_id', $request->input('warehouse_id'))
                    ->ignore($warehouseArea->id),
            ],
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['name_key'] = $this->makeKey($validated['name']);

        $warehouseArea->update($validated);

        return back()->with('success', 'Warehouse area updated successfully.');
    }

    public function destroy(WarehouseArea $warehouseArea)
    {
        $warehouseArea->delete();

        return back()->with('success', 'Warehouse area deleted successfully.');
    }
}

