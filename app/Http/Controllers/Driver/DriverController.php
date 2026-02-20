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
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Scope: all DOs that belong to this driver
        $driverScope = function ($q) use ($user, $driverVehicleIds) {
            $q->where('driver_user_id', $user->id)
              ->orWhere('delivered_by', $user->id);
            if (!empty($driverVehicleIds)) {
                $q->orWhereIn('vehicle_id', $driverVehicleIds);
            }
        };

        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit', 'salesOrder'])
            ->whereIn('status', ['shipped', 'picking'])
            ->where($driverScope)
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

        // Trip stats - using same expanded scope
        $tripStats = [
            'total' => DeliveryOrder::where($driverScope)->count(),
            'delivered' => DeliveryOrder::where($driverScope)->whereIn('status', ['delivered', 'completed'])->count(),
            'in_progress' => DeliveryOrder::where($driverScope)->whereIn('status', ['shipped', 'picking'])->count(),
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

        return response()->json(['deliveryOrder' => $deliveryOrder]);
    }

    public function confirmArrival(Request $request, DeliveryOrder $deliveryOrder)
    {
        $user = $request->user();

        if ($deliveryOrder->status !== 'shipped') {
            return back()->with('error', 'Hanya DO berstatus Shipped yang bisa dikonfirmasi sampai.');
        }

        $deliveryOrder->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'delivered_by' => $user->id,
        ]);

        return redirect()->route('driver.dashboard')->with('success', 'DO ' . $deliveryOrder->do_number . ' dikonfirmasi sampai! ✅');
    }
}
