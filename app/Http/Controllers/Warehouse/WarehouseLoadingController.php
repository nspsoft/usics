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

    public function updateItemQty(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'item_id' => 'required|exists:delivery_order_items,id',
            'qty' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        if (!in_array($deliveryOrder->status, ['draft', 'picking'])) {
            return back()->with('error', 'Hanya bisa revisi qty saat status Draft atau Picking (Sedang Loading).');
        }

        $item = $deliveryOrder->items()->findOrFail($request->item_id);

        if ($request->qty > $item->qty_ordered) {
            return back()->with('error', 'Quantity dikirim tidak boleh melebihi qty order.');
        }

        $oldQty = $item->qty_delivered;
        $item->update([
            'qty_delivered' => $request->qty,
            'notes' => $request->reason ? ($item->notes ? $item->notes . " | " : "") . "Revisi: " . $request->reason : $item->notes,
        ]);

        // Recalculate SO Item totals (handled by model event saved in DeliveryOrderItem)

        activity()
            ->performedOn($deliveryOrder)
            ->withProperties([
                'item' => $item->product->name,
                'old_qty' => $oldQty,
                'new_qty' => $request->qty,
                'reason' => $request->reason,
            ])
            ->log('Gudang merevisi jumlah muatan item ' . $item->product->name);

        return back()->with('success', 'Quantity item ' . $item->product->name . ' berhasil direvisi.');
    }

    public function toggleItemLoaded(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'item_id' => 'required|exists:delivery_order_items,id',
            'is_loaded' => 'required|boolean',
        ]);

        if ($deliveryOrder->status !== 'picking') {
            return back()->with('error', 'Item loading can only be tracked during "Loading in Progress" (Picking) phase.');
        }

        $item = $deliveryOrder->items()->findOrFail($request->item_id);
        $item->update(['is_loaded' => $request->is_loaded]);

        return back()->with('success', $request->is_loaded ? 'Item marked as loaded.' : 'Item marked as not loaded.');
    }
}
