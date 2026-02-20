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

        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit', 'salesOrder'])
            ->where('status', 'shipped')
            ->where(function ($q) use ($user) {
                $q->where('driver_user_id', $user->id)
                  ->orWhere('delivered_by', $user->id);
            })
            ->orderBy('delivery_date')
            ->get();

        // Find the driver's vehicle - from assigned DOs or by name match
        $vehicle = null;
        $latestDo = DeliveryOrder::where('driver_user_id', $user->id)
            ->whereNotNull('vehicle_id')
            ->latest()
            ->first();

        if ($latestDo) {
            $vehicle = Vehicle::find($latestDo->vehicle_id);
        }
        
        if (!$vehicle) {
            $vehicle = Vehicle::where('driver_name', $user->name)->where('is_active', true)->first();
        }

        // Trip stats
        $tripStats = [
            'total' => DeliveryOrder::where('driver_user_id', $user->id)->count(),
            'delivered' => DeliveryOrder::where('driver_user_id', $user->id)->where('status', 'completed')->count(),
            'in_progress' => DeliveryOrder::where('driver_user_id', $user->id)->whereIn('status', ['shipped', 'delivered'])->count(),
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
