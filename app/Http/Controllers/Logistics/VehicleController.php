<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): Response
    {
        $vehicles = Vehicle::query()
            ->whereIn('usage_type', ['logistics', 'both'])
            ->when($request->search, function ($q, $search) {
                $q->where('license_plate', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('license_plate')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Logistics/Vehicle/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['search', 'status']),
            'vehicleStatuses' => [
                ['value' => 'available', 'label' => 'Available'],
                ['value' => 'maintenance', 'label' => 'Maintenance'],
                ['value' => 'busy', 'label' => 'Busy'],
            ],
        ]);
    }

    /**
     * Display the specified vehicle with delivery history.
     */
    public function show(Vehicle $vehicle): Response
    {
        $vehicle->load([
            'deliveryOrders' => function ($query) {
                $query->with(['customer', 'items.product'])
                      ->latest();
            }
        ]);

        return Inertia::render('Logistics/Vehicle/Show', [
            'vehicle' => $vehicle,
            'stats' => [
                'total_trips' => $vehicle->deliveryOrders()->count(),
                'completed_trips' => $vehicle->deliveryOrders()->where('status', 'delivered')->count(),
                'pending_trips' => $vehicle->deliveryOrders()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            ]
        ]);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'rfid_card' => 'nullable|string|max:50|unique:vehicles,rfid_card',
            'traccar_device_id' => 'nullable|integer|unique:vehicles,traccar_device_id',
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'stnk_number' => 'nullable|string|max:50',
            'stnk_expiry' => 'nullable|date',
            'kir_number' => 'nullable|string|max:50',
            'kir_expiry' => 'nullable|date',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|numeric|min:1900|max:' . (date('Y') + 1),
            'chassis_number' => 'nullable|string|max:100',
            'engine_number' => 'nullable|string|max:100',
            'fuel_type' => 'nullable|string|max:50',
            'ownership' => 'nullable|string|max:50',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'driver_photo' => 'nullable|image|max:2048',
            'vehicle_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('driver_photo')) {
            $validated['driver_photo'] = $request->file('driver_photo')->store('vehicles/drivers', 'public');
        }

        if ($request->hasFile('vehicle_photo')) {
            $validated['vehicle_photo'] = $request->file('vehicle_photo')->store('vehicles/fleet', 'public');
        }

        $validated['usage_type'] = 'logistics';
        Vehicle::create($validated);

        return redirect()->back()->with('success', 'Vehicle created successfully.');
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate,' . $vehicle->id,
            'rfid_card' => 'nullable|string|max:50|unique:vehicles,rfid_card,' . $vehicle->id,
            'traccar_device_id' => 'nullable|integer|unique:vehicles,traccar_device_id,' . $vehicle->id,
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'stnk_number' => 'nullable|string|max:50',
            'stnk_expiry' => 'nullable|date',
            'kir_number' => 'nullable|string|max:50',
            'kir_expiry' => 'nullable|date',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|numeric|min:1900|max:' . (date('Y') + 1),
            'chassis_number' => 'nullable|string|max:100',
            'engine_number' => 'nullable|string|max:100',
            'fuel_type' => 'nullable|string|max:50',
            'ownership' => 'nullable|string|max:50',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'driver_photo' => 'nullable|image|max:2048',
            'vehicle_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('driver_photo')) {
            // Delete old photo
            if ($vehicle->driver_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($vehicle->driver_photo);
            }
            $validated['driver_photo'] = $request->file('driver_photo')->store('vehicles/drivers', 'public');
        }

        if ($request->hasFile('vehicle_photo')) {
            // Delete old photo
            if ($vehicle->vehicle_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($vehicle->vehicle_photo);
            }
            $validated['vehicle_photo'] = $request->file('vehicle_photo')->store('vehicles/fleet', 'public');
        }

        $vehicle->update($validated);

        return redirect()->back()->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Check if vehicle is busy
        if ($vehicle->status === 'busy') {
            return back()->with('error', 'Cannot delete a vehicle that is currently busy.');
        }

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted successfully.');
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Logistics\VehicleExport, 'vehicle_fleet.xlsx');
    }

    public function template()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Template\VehicleTemplateExport, 'vehicle_import_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\Logistics\VehicleImport, $request->file('file'));
            return redirect()->back()->with('success', 'Vehicles imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // FORCE DISPLAY ERRORS (Since Frontend UI is broken on this machine)
            $failures = $e->failures();
            
            $readableErrors = [];
            foreach ($failures as $failure) {
                $readableErrors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }
            
            dd('IMPORT GAGAL KARENA VALIDASI:', $readableErrors);
        } catch (\Exception $e) {
            dd('IMPORT GAGAL (SYSTEM ERROR):', $e->getMessage());
        }
    }
}
