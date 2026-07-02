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
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/machines'), $filename);
            $validated['image_path'] = '/uploads/machines/' . $filename;
        }

        Machine::create($validated);

        return redirect()->route('manufacturing.machines.index')
            ->with('success', 'Machine created successfully.');
    }

    public function update(Request $request, Machine $machine)
    {
        // Support method spoofing for multipart/form-data
        if ($request->input('_method') === 'PUT') {
            $request->merge(['_method' => 'PUT']);
        }

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
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($machine->image_path && file_exists(public_path($machine->image_path))) {
                @unlink(public_path($machine->image_path));
            }
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/machines'), $filename);
            $validated['image_path'] = '/uploads/machines/' . $filename;
        }

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
