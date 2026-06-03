<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Find vehicles owned by this driver (by name match)
        $driverVehicleIds = Vehicle::where('driver_name', $user->name)
            ->pluck('id')
            ->toArray();

        // Collect all DO IDs belonging to this driver
        $driverDoIds = DeliveryOrder::where(function ($q) use ($user, $driverVehicleIds) {
                $q->where('driver_user_id', $user->id)
                  ->orWhere('delivered_by', $user->id);
                if (!empty($driverVehicleIds)) {
                    $q->orWhereIn('vehicle_id', $driverVehicleIds);
                }
            })
            ->pluck('id')
            ->toArray();

        // Active DOs (shipped/picking only)
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit', 'salesOrder'])
            ->whereIn('id', $driverDoIds)
            ->whereIn('status', ['shipped', 'picking'])
            ->orderBy('delivery_date')
            ->get();

        // Find the driver's vehicle
        $vehicle = null;
        if (!empty($driverVehicleIds)) {
            $vehicle = Vehicle::find($driverVehicleIds[0]);
        }
        if (!$vehicle) {
            $latestDo = DeliveryOrder::where('driver_user_id', $user->id)
                ->whereNotNull('vehicle_id')
                ->latest()
                ->first();
            if ($latestDo) {
                $vehicle = Vehicle::find($latestDo->vehicle_id);
            }
        }

        // Trip stats from all driver DOs
        $allDriverDos = DeliveryOrder::whereIn('id', $driverDoIds)->get();
        $tripStats = [
            'total' => $allDriverDos->count(),
            'delivered' => $allDriverDos->whereIn('status', ['delivered', 'completed'])->count(),
            'in_progress' => $allDriverDos->whereIn('status', ['shipped', 'picking'])->count(),
        ];

        return Inertia::render('Driver/Dashboard', [
            'deliveryOrders' => $deliveryOrders,
            'vehicle' => $vehicle,
            'tripStats' => $tripStats,
        ]);
    }

    public function scan()
    {
        return Inertia::render('Driver/Scanner');
    }

    public function lookupDo(Request $request)
    {
        $request->validate(['uuid' => 'required|string']);
        
        $deliveryOrder = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit'])
            ->where('id', $request->uuid)
            ->first();

        if (!$deliveryOrder) {
            return response()->json(['error' => 'DO tidak ditemukan.'], 404);
        }

        if ($deliveryOrder->status !== 'shipped') {
            return response()->json(['error' => 'DO ini belum berstatus SHIPPED. Status saat ini: ' . strtoupper($deliveryOrder->status)], 422);
        }

        $shipmentNumber = $deliveryOrder->shipment_number;
        $isLastDo = true;
        $primaryDo = null;
        $travelAllowance = 0;
        $requiresCosts = false;

        if ($shipmentNumber) {
            // Check if there are other DOs in this shipment that are still active (not delivered, completed, or cancelled)
            $otherPendingExists = DeliveryOrder::where('shipment_number', $shipmentNumber)
                ->where('id', '!=', $deliveryOrder->id)
                ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
                ->exists();
            $isLastDo = !$otherPendingExists;

            $primaryDo = DeliveryOrder::where('shipment_number', $shipmentNumber)
                ->orderBy('id')
                ->first();

            if ($primaryDo) {
                $travelAllowance = (float) $primaryDo->travel_allowance;
                if ($isLastDo && $travelAllowance > 0 && $primaryDo->travel_allowance_status === 'paid') {
                    $requiresCosts = true;
                }
            }
        }

        return response()->json([
            'deliveryOrder' => $deliveryOrder,
            'is_last_do' => $isLastDo,
            'requires_costs' => $requiresCosts,
            'travel_allowance' => $travelAllowance,
        ]);
    }

    public function confirmArrival(Request $request, DeliveryOrder $deliveryOrder)
    {
        $user = $request->user();

        if ($deliveryOrder->status !== 'shipped') {
            return back()->with('error', 'Hanya DO berstatus Shipped yang bisa dikonfirmasi sampai.');
        }

        $shipmentNumber = $deliveryOrder->shipment_number;
        $isLastDo = true;

        if ($shipmentNumber) {
            // Check if there are other DOs in this shipment that are still active (not delivered, completed, or cancelled)
            $otherPendingExists = DeliveryOrder::where('shipment_number', $shipmentNumber)
                ->where('id', '!=', $deliveryOrder->id)
                ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
                ->exists();
            $isLastDo = !$otherPendingExists;
        }

        $primaryDo = null;
        if ($shipmentNumber) {
            $primaryDo = DeliveryOrder::where('shipment_number', $shipmentNumber)
                ->orderBy('id')
                ->first();
        }

        $requiresCosts = false;
        if ($isLastDo && $primaryDo && $primaryDo->travel_allowance > 0 && $primaryDo->travel_allowance_status === 'paid') {
            $requiresCosts = true;
        }

        $costData = [];
        if ($requiresCosts || $request->has('odometer_end')) {
            $rules = [
                'odometer_end' => $requiresCosts ? 'required|integer|gt:0' : 'nullable|integer|gt:0',
                'real_fuel_cost' => $requiresCosts ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
                'real_toll_cost' => $requiresCosts ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
                'real_other_cost' => $requiresCosts ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
                'image' => 'nullable|image|max:5120',
            ];

            $validated = $request->validate($rules);

            if (isset($validated['odometer_end'])) {
                $costData = [
                    'odometer_end' => $validated['odometer_end'],
                    'real_fuel_cost' => $validated['real_fuel_cost'] ?? 0,
                    'real_toll_cost' => $validated['real_toll_cost'] ?? 0,
                    'real_other_cost' => $validated['real_other_cost'] ?? 0,
                ];
                if ($primaryDo && $primaryDo->travel_allowance > 0) {
                    $costData['travel_allowance_status'] = 'reconciled';
                }
                if ($request->hasFile('image')) {
                    $costData['real_costs_receipt_path'] = $request->file('image')->store('delivery_receipts', 'public');
                }
            }
        }

        $deliveryOrder->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'delivered_by' => $user->id,
        ]);

        if (!empty($costData) && $primaryDo) {
            $primaryDo->update($costData);
        }

        return redirect()->route('driver.dashboard')->with('success', 'DO ' . $deliveryOrder->do_number . ' dikonfirmasi sampai! ' . ($requiresCosts ? 'Biaya perjalanan ritase berhasil dilaporkan.' : ''));
    }
}
