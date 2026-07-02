<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MachineController extends Controller
{
    public function index()
    {
        return Inertia::render('Manufacturing/Machines/Index', [
            'machines' => Machine::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:machines,name',
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:255',
            'maker' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'runtime_hours' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        Machine::create($validated);

        return redirect()->route('manufacturing.machines.index')
            ->with('success', 'Machine created successfully.');
    }

    public function update(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:machines,name,' . $machine->id,
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:255',
            'maker' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'runtime_hours' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $machine->update($validated);

        return redirect()->route('manufacturing.machines.index')
            ->with('success', 'Machine updated successfully.');
    }

    public function destroy(Machine $machine)
    {
        $machine->delete();

        return redirect()->route('manufacturing.machines.index')
            ->with('success', 'Machine deleted successfully.');
    }
}
