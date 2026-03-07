<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ProjectBlueprint;
use Inertia\Inertia;

class BlueprintInteractiveController extends Controller
{
    public function index()
    {
        // Hierarchal fetch mapping tree relationships automatically via Eloquent
        $blueprints = ProjectBlueprint::whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->orderBy('order_index');
            }])
            ->orderBy('order_index')
            ->get();

        return Inertia::render('Project/Blueprint/Index', [
            'blueprints' => $blueprints
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'order_index' => 'nullable|integer',
            'parent_id' => 'nullable|exists:project_blueprints,id'
        ]);

        if (empty($validated['order_index'])) {
            $validated['order_index'] = ProjectBlueprint::where('parent_id', $validated['parent_id'] ?? null)->max('order_index') + 1;
        }

        ProjectBlueprint::create($validated);

        return back()->with('success', 'Bab Blueprint berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $blueprint = ProjectBlueprint::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'order_index' => 'integer',
            'parent_id' => 'nullable|exists:project_blueprints,id'
        ]);

        $blueprint->update($validated);

        return back()->with('success', 'Bab Blueprint berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $blueprint = ProjectBlueprint::findOrFail($id);
        $blueprint->delete();

        return back()->with('success', 'Bab Blueprint berhasil dihapus.');
    }
}
