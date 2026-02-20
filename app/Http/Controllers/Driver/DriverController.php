<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
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

        return Inertia::render('Driver/Dashboard', [
            'deliveryOrders' => $deliveryOrders,
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
