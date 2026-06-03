<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GaVehicleController extends Controller
{
    /**
     * Display a listing of GA passenger vehicles.
     */
    public function index(Request $request): Response
    {
        $vehicles = Vehicle::query()
            ->whereIn('usage_type', ['passenger', 'both'])
            ->when($request->search, function ($q, $search) {
                $q->where(function($query) use ($search) {
                    $query->where('license_plate', 'like', "%{$search}%")
                          ->orWhere('driver_name', 'like', "%{$search}%")
                          ->orWhere('vehicle_type', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('license_plate')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GeneralAffair/Fleet/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['search', 'status']),
            'vehicleStatuses' => [
                ['value' => 'available', 'label' => 'Available'],
                ['value' => 'maintenance', 'label' => 'Maintenance'],
                ['value' => 'busy', 'label' => 'Busy'],
                ['value' => 'in_use', 'label' => 'In Use'],
            ],
        ]);
    }

    /**
     * Display the specified GA vehicle details & booking history.
     */
    public function show(Vehicle $vehicle): Response
    {
        $vehicle->load([
            'gaBookings' => function ($query) {
                $query->with(['user', 'trip'])
                      ->latest();
            }
        ]);

        return Inertia::render('GeneralAffair/Fleet/Show', [
            'vehicle' => $vehicle,
            'stats' => [
                'total_bookings' => $vehicle->gaBookings()->count(),
                'completed_trips' => $vehicle->gaBookings()->where('status', 'completed')->count(),
                'pending_bookings' => $vehicle->gaBookings()->where('status', 'pending')->count(),
            ]
        ]);
    }

    /**
     * Store a newly created GA vehicle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'traccar_device_id' => 'nullable|integer|unique:vehicles,traccar_device_id',
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy,in_use',
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

        $validated['usage_type'] = 'passenger';

        Vehicle::create($validated);

        return redirect()->back()->with('success', 'GA Vehicle created successfully.');
    }

    /**
     * Update the specified GA vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate,' . $vehicle->id,
            'traccar_device_id' => 'nullable|integer|unique:vehicles,traccar_device_id,' . $vehicle->id,
            'vehicle_type' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,busy,in_use',
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
            if ($vehicle->driver_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($vehicle->driver_photo);
            }
            $validated['driver_photo'] = $request->file('driver_photo')->store('vehicles/drivers', 'public');
        }

        if ($request->hasFile('vehicle_photo')) {
            if ($vehicle->vehicle_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($vehicle->vehicle_photo);
            }
            $validated['vehicle_photo'] = $request->file('vehicle_photo')->store('vehicles/fleet', 'public');
        }

        $vehicle->update($validated);

        return redirect()->back()->with('success', 'GA Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->status === 'busy' || $vehicle->status === 'in_use') {
            return back()->with('error', 'Cannot delete a vehicle that is currently in use.');
        }

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted successfully.');
    }
}
