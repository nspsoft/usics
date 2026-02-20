<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseLoadingController extends Controller
{
    public function index(Request $request)
    {
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit', 'salesOrder'])
            ->whereIn('status', ['draft', 'picking'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->warehouse_id, function ($q, $warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->orderBy('delivery_date')
            ->get();

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Warehouse/LoadingQueue', [
            'deliveryOrders' => $deliveryOrders,
            'warehouses' => $warehouses,
            'filters' => $request->only(['search', 'warehouse_id']),
        ]);
    }

    public function updateStatus(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'status' => 'required|in:picking,packed',
        ]);

        // Only allow transitions: draft->picking, picking->packed
        $allowed = [
            'draft' => ['picking'],
            'picking' => ['packed'],
        ];

        if (!isset($allowed[$deliveryOrder->status]) || !in_array($request->status, $allowed[$deliveryOrder->status])) {
            return back()->with('error', 'Transisi status tidak valid.');
        }

        $deliveryOrder->update(['status' => $request->status]);

        $messages = [
            'picking' => 'Loading dimulai! Status berubah ke Picking.',
            'packed' => 'Barang siap muat! Status berubah ke Packed.',
        ];

        return back()->with('success', $messages[$request->status] ?? 'Status updated.');
    }
}
