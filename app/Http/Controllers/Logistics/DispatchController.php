<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit', 'salesOrder'])
            ->where('status', 'packed')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('delivery_date')
            ->paginate(10)->withQueryString();

        $vehicles = Vehicle::where('is_active', true)->orderBy('license_plate')->get();

        return Inertia::render('Logistics/Dispatch/Index', [
            'deliveryOrders' => $deliveryOrders,
            'vehicles' => $vehicles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function dispatch(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'packed') {
            return back()->with('error', 'Hanya DO berstatus Packed yang bisa diberangkatkan.');
        }

        // Ensure vehicle info is present
        if (!$deliveryOrder->vehicle_number) {
            return back()->with('error', 'Armada belum ditetapkan. Assign kendaraan terlebih dahulu via Delivery Planning.');
        }

        $deliveryOrder->update(['status' => 'shipped']);

        return back()->with('success', 'DO ' . $deliveryOrder->do_number . ' telah diberangkatkan! Status: Shipped.');
    }
}
