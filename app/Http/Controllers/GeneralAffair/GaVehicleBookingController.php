<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GaVehicleBooking;
use App\Models\GaVehicleTrip;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class GaVehicleBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = GaVehicleBooking::with(['user', 'vehicle', 'trip']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('purpose', 'like', "%{$request->search}%")
                  ->orWhere('destination', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        
        // Get vehicles catalog to show status
        $vehicles = Vehicle::whereIn('usage_type', ['passenger', 'both'])->get();

        return inertia('GeneralAffair/Vehicles/Index', [
            'bookings' => $bookings,
            'vehicles' => $vehicles,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return inertia('GeneralAffair/Vehicles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'passengers_count' => 'required|integer|min:1',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        GaVehicleBooking::create($validated);

        return redirect()->route('ga.vehicle-bookings.index')->with('success', 'Vehicle booking requested successfully.');
    }

    public function show($id)
    {
        $booking = GaVehicleBooking::with(['user', 'vehicle', 'trip'])->findOrFail($id);
        
        // Get all active vehicles for admin assignment
        $vehicles = Vehicle::where('is_active', true)->whereIn('usage_type', ['passenger', 'both'])->get();

        return inertia('GeneralAffair/Vehicles/Show', [
            'booking' => $booking,
            'vehicles' => $vehicles,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $booking = GaVehicleBooking::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_name' => 'nullable|string|max:255',
            'approval_notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        $booking->update([
            'vehicle_id' => $validated['vehicle_id'],
            'driver_name' => $validated['driver_name'] ?: $vehicle->driver_name,
            'approval_notes' => $validated['approval_notes'],
            'status' => 'approved',
        ]);

        return redirect()->route('ga.vehicle-bookings.show', $booking->id)->with('success', 'Booking approved and vehicle assigned.');
    }

    public function reject(Request $request, $id)
    {
        $booking = GaVehicleBooking::findOrFail($id);

        $validated = $request->validate([
            'approval_notes' => 'required|string',
        ]);

        $booking->update([
            'approval_notes' => $validated['approval_notes'],
            'status' => 'rejected',
        ]);

        return redirect()->route('ga.vehicle-bookings.show', $booking->id)->with('success', 'Booking request rejected.');
    }

    public function startTrip(Request $request, $id)
    {
        $booking = GaVehicleBooking::findOrFail($id);

        $validated = $request->validate([
            'odometer_start' => 'required|integer|min:0',
        ]);

        // Start trip log
        GaVehicleTrip::create([
            'ga_vehicle_booking_id' => $booking->id,
            'vehicle_id' => $booking->vehicle_id,
            'odometer_start' => $validated['odometer_start'],
        ]);

        $booking->update([
            'status' => 'active',
        ]);

        // Update Vehicle Status
        if ($booking->vehicle) {
            $booking->vehicle->update([
                'status' => 'in_use',
            ]);
        }

        return redirect()->route('ga.vehicle-bookings.show', $booking->id)->with('success', 'Trip started successfully.');
    }

    public function completeTrip(Request $request, $id)
    {
        $booking = GaVehicleBooking::with('trip')->findOrFail($id);
        $trip = $booking->trip;

        $validated = $request->validate([
            'odometer_end' => 'required|integer|gt:' . $trip->odometer_start,
            'fuel_liters' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'toll_cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($trip->receipt_path) {
                Storage::disk('public')->delete($trip->receipt_path);
            }
            $validated['receipt_path'] = $request->file('image')->store('ga_vehicle_receipts', 'public');
        }

        $trip->update($validated);

        $booking->update([
            'status' => 'completed',
        ]);

        // Update Vehicle Status back to Available
        if ($booking->vehicle) {
            $booking->vehicle->update([
                'status' => 'available',
            ]);
        }

        return redirect()->route('ga.vehicle-bookings.show', $booking->id)->with('success', 'Trip completed and logs recorded.');
    }
}
