<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GaLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = \App\Models\GaLocation::all();
        return inertia('GeneralAffair/Locations/Index', [
            'locations' => $locations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'map_background' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('map_background')) {
            $validated['map_background_path'] = $request->file('map_background')->store('ga_maps', 'public');
        }

        \App\Models\GaLocation::create($validated);
        return redirect()->back()->with('success', 'Location created successfully.');
    }

    public function update(Request $request, \App\Models\GaLocation $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'map_background' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('map_background')) {
            if ($location->map_background_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($location->map_background_path);
            }
            $validated['map_background_path'] = $request->file('map_background')->store('ga_maps', 'public');
        }

        $location->update($validated);
        return redirect()->back()->with('success', 'Location updated successfully.');
    }

    public function destroy(\App\Models\GaLocation $location)
    {
        if ($location->map_background_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($location->map_background_path);
        }
        $location->delete();
        return redirect()->back()->with('success', 'Location deleted successfully.');
    }

}
