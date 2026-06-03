<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GaAsset;
use App\Models\GaLocation;
use Illuminate\Support\Facades\Storage;

class GaAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = GaAsset::with(['gaLocation', 'pic']);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('asset_code', 'like', "%{$request->search}%");
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $assets = $query->paginate(10)->withQueryString();

        return inertia('GeneralAffair/Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function create()
    {
        $locations = GaLocation::all();
        $users = \App\Models\User::all(['id', 'name']);
        
        return inertia('GeneralAffair/Assets/Create', [
            'locations' => $locations,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|unique:ga_assets,asset_code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric',
            'condition' => 'nullable|string',
            'location' => 'nullable|string',
            'ga_location_id' => 'nullable|exists:ga_locations,id',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'user_id' => 'nullable|exists:users,id',
            'image' => 'nullable|image|max:5120',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('ga_assets', 'public');
        }

        $asset = GaAsset::create($validated);
        
        $asset->logs()->create([
            'action' => 'created',
            'notes' => 'Asset initially registered.',
            'user_id' => auth()->id()
        ]);

        return redirect()->route('ga.assets.index')->with('success', 'Asset created successfully.');
    }

    public function show($id)
    {
        $asset = GaAsset::with(['gaLocation', 'pic', 'logs.user'])->findOrFail($id);
        return inertia('GeneralAffair/Assets/Show', [
            'asset' => $asset
        ]);
    }

    public function edit($id)
    {
        $asset = GaAsset::findOrFail($id);
        $locations = GaLocation::all();
        $users = \App\Models\User::all(['id', 'name']);

        return inertia('GeneralAffair/Assets/Edit', [
            'asset' => $asset,
            'locations' => $locations,
            'users' => $users
        ]);
    }

    public function update(Request $request, $id)
    {
        $asset = GaAsset::findOrFail($id);

        $validated = $request->validate([
            'asset_code' => 'required|string|unique:ga_assets,asset_code,' . $asset->id,
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric',
            'condition' => 'nullable|string',
            'location' => 'nullable|string',
            'ga_location_id' => 'nullable|exists:ga_locations,id',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'user_id' => 'nullable|exists:users,id',
            'image' => 'nullable|image|max:5120',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('image')) {
            if ($asset->image_path) {
                Storage::disk('public')->delete($asset->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('ga_assets', 'public');
        }

        $asset->update($validated);

        // Simple logging logic if status changed or just a general update
        $asset->logs()->create([
            'action' => 'updated',
            'notes' => 'Asset details updated.',
            'user_id' => auth()->id()
        ]);

        return redirect()->route('ga.assets.index')->with('success', 'Asset updated successfully.');
    }

    public function destroy($id)
    {
        $asset = GaAsset::findOrFail($id);
        if ($asset->image_path) {
            Storage::disk('public')->delete($asset->image_path);
        }
        $asset->delete();

        return redirect()->route('ga.assets.index')->with('success', 'Asset deleted successfully.');
    }
}
