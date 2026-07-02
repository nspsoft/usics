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
            ->paginate(10)->withQueryString();

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

    public function display()
    {
        // 1. Called/picking orders (status: picking, ordered by called_at desc)
        $called = DeliveryOrder::with(['customer', 'vehicle', 'warehouse'])
            ->where('status', 'picking')
            ->whereNotNull('called_at')
            ->orderBy('called_at', 'desc')
            ->get();

        // 2. Queued/draft orders (status: draft, ordered by delivery_date asc)
        $queued = DeliveryOrder::with(['customer', 'vehicle', 'warehouse'])
            ->where('status', 'draft')
            ->orderBy('delivery_date')
            ->get();

        // 3. Recently completed loading (status: packed, ordered by updated_at desc, limit 5)
        $completed = DeliveryOrder::with(['customer', 'vehicle', 'warehouse'])
            ->where('status', 'packed')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Warehouse/LoadingDisplay', [
            'called' => $called,
            'queued' => $queued,
            'completed' => $completed,
        ]);
    }

    public function call(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'loading_bay' => 'required|string|max:50',
        ]);

        if (!in_array($deliveryOrder->status, ['draft', 'picking'])) {
            return back()->with('error', 'Hanya bisa memanggil truk dengan status Draft atau Sedang Loading.');
        }

        $deliveryOrder->update([
            'status' => 'picking',
            'loading_bay' => $request->loading_bay,
            'called_at' => now(),
        ]);

        activity()
            ->performedOn($deliveryOrder)
            ->withProperties([
                'driver' => $deliveryOrder->driver_name,
                'vehicle' => $deliveryOrder->vehicle_number ?? ($deliveryOrder->vehicle->license_plate ?? 'N/A'),
                'loading_bay' => $request->loading_bay,
            ])
            ->log('Memanggil supir ' . $deliveryOrder->driver_name . ' ke ' . $request->loading_bay);

        // Send WhatsApp notification if driver has a phone number
        $phone = null;
        if ($deliveryOrder->driver_user_id) {
            $driver = \App\Models\User::with('employee')->find($deliveryOrder->driver_user_id);
            if ($driver && $driver->employee && $driver->employee->phone) {
                $phone = $driver->employee->phone;
            }
        }

        if ($phone) {
            try {
                $waService = app(\App\Services\WhatsappBotService::class);
                $message = "PANGGILAN LOGISTIK: Halo Pak {$deliveryOrder->driver_name}, silakan membawa armada Anda dengan nomor plat {$deliveryOrder->vehicle_number} memasuki {$request->loading_bay} untuk memulai loading barang DO #{$deliveryOrder->do_number}.";
                $waService->sendNotification($phone, $message);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send WhatsApp call: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Supir berhasil dipanggil ke ' . $request->loading_bay);
    }

    public function rfidIndex()
    {
        // 1. Fetch active delivery orders that are in the queue flow (draft, picking, packed)
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse'])
            ->whereIn('status', ['draft', 'picking', 'packed'])
            ->orderBy('delivery_date')
            ->get();

        // 2. Fetch the last 15 RFID scans
        $scans = \App\Models\RfidScanLog::with('deliveryOrder.customer')
            ->latest()
            ->limit(15)
            ->get();

        return Inertia::render('Warehouse/RfidSimulator', [
            'deliveryOrders' => $deliveryOrders,
            'scans' => $scans,
        ]);
    }

    /**
     * Security Gate RFID Dashboard — Pos Satpam
     */
    public function securityGateIndex()
    {
        $deliveryOrders = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit'])
            ->whereIn('status', ['draft', 'picking', 'packed'])
            ->orderBy('delivery_date')
            ->get();

        $scans = \App\Models\RfidScanLog::with('deliveryOrder.customer')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Warehouse/SecurityGate', [
            'deliveryOrders' => $deliveryOrders,
            'scans' => $scans,
        ]);
    }

    /**
     * Handle RFID tap scan at Security Gate
     */
    public function securityGateScan(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'action' => 'required|in:entry,exit',
        ]);

        $deliveryOrder = DeliveryOrder::with(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit'])
            ->findOrFail($request->delivery_order_id);

        $plate = $deliveryOrder->vehicle_number ?? ($deliveryOrder->vehicle->license_plate ?? 'TANPA-PLAT');
        $tagId = "RFID-TRUCK-" . str_replace(' ', '', strtoupper($plate));
        $vehicle = $deliveryOrder->vehicle;

        $status = 'success';
        $message = '';

        if ($request->action === 'entry') {
            if ($deliveryOrder->status !== 'draft') {
                $status = 'warning';
                $message = "Truk {$plate} sudah tercatat masuk sebelumnya. Status saat ini: " . strtoupper($deliveryOrder->status);
            } else {
                $message = "✅ GATE ENTRY: Truk {$plate} (DO #{$deliveryOrder->do_number}) berhasil memasuki area pabrik.";
            }
        } else {
            // exit
            if ($deliveryOrder->status !== 'packed') {
                $status = 'error';
                $message = "❌ GATE EXIT DITOLAK: Truk {$plate} belum selesai proses loading/timbangan. Status: " . strtoupper($deliveryOrder->status);
            } else {
                $deliveryOrder->update([
                    'status' => 'shipped',
                    'delivered_at' => now(),
                ]);
                $message = "✅ GATE EXIT: Truk {$plate} (DO #{$deliveryOrder->do_number}) berhasil keluar pabrik. Status: SHIPPED.";
            }
        }

        // Log scan
        \App\Models\RfidScanLog::create([
            'delivery_order_id' => $deliveryOrder->id,
            'tag_id' => $tagId,
            'reader_id' => $request->action === 'entry' ? 'gate_entry' : 'gate_exit',
            'simulated_weight' => null,
            'status' => $status,
            'message' => $message,
        ]);

        // Activity log
        if ($status !== 'error') {
            activity()
                ->performedOn($deliveryOrder)
                ->withProperties([
                    'action' => $request->action,
                    'tag_id' => $tagId,
                    'gate' => $request->action === 'entry' ? 'Pintu Masuk' : 'Pintu Keluar',
                ])
                ->log("Security Gate RFID: " . $message);
        }

        // Build vehicle compliance data
        $compliance = [];
        if ($vehicle) {
            $now = now();
            $compliance = [
                'stnk' => [
                    'number' => $vehicle->stnk_number,
                    'expiry' => $vehicle->stnk_expiry,
                    'status' => !$vehicle->stnk_expiry ? 'unknown'
                        : ($vehicle->stnk_expiry < $now ? 'expired'
                        : ($vehicle->stnk_expiry < $now->copy()->addDays(30) ? 'near_expiry' : 'active')),
                ],
                'kir' => [
                    'number' => $vehicle->kir_number,
                    'expiry' => $vehicle->kir_expiry,
                    'status' => !$vehicle->kir_expiry ? 'unknown'
                        : ($vehicle->kir_expiry < $now ? 'expired'
                        : ($vehicle->kir_expiry < $now->copy()->addDays(30) ? 'near_expiry' : 'active')),
                ],
            ];
        }

        return back()->with($status === 'error' ? 'error' : 'success', $message)
            ->with('scannedData', [
                'delivery_order' => $deliveryOrder->fresh(['customer', 'vehicle', 'warehouse', 'items.product', 'items.unit']),
                'vehicle_photo' => $vehicle?->vehicle_photo_url,
                'driver_photo' => $vehicle?->driver_photo_url,
                'compliance' => $compliance,
                'scan_status' => $status,
                'scan_message' => $message,
                'scan_action' => $request->action,
                'scan_time' => now()->toDateTimeString(),
            ]);
    }

    public function rfidSimulate(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'reader_id' => 'required|in:gate_entry,weighbridge_in,bay_loading,weighbridge_out,gate_exit',
            'weight' => 'nullable|numeric|min:0',
            'loading_bay' => 'nullable|string|max:50',
        ]);

        $deliveryOrder = DeliveryOrder::with(['customer', 'vehicle'])->findOrFail($request->delivery_order_id);
        $plate = $deliveryOrder->vehicle_number ?? ($deliveryOrder->vehicle->license_plate ?? 'TANPA-PLAT');
        $tagId = "RFID-TRUCK-" . str_replace(' ', '', strtoupper($plate));

        $status = 'success';
        $message = '';
        $transitionSuccess = true;

        switch ($request->reader_id) {
            case 'gate_entry':
                // Transition status: only valid if DO is draft
                if ($deliveryOrder->status !== 'draft') {
                    $status = 'warning';
                    $message = "Truk {$plate} terdeteksi di Pintu Masuk, tetapi status pengiriman sudah: " . strtoupper($deliveryOrder->status);
                } else {
                    $message = "Truk {$plate} (DO #{$deliveryOrder->do_number}) Berhasil Masuk Gate. Menunggu Jembatan Timbang.";
                }
                break;

            case 'weighbridge_in':
                if (!in_array($deliveryOrder->status, ['draft', 'picking'])) {
                    $status = 'error';
                    $message = "Gagal menimbang: Status DO #{$deliveryOrder->do_number} adalah " . strtoupper($deliveryOrder->status) . " (Harus Draft/Picking).";
                    $transitionSuccess = false;
                } else {
                    $weight = $request->weight ?? 8200; // default tare
                    $notes = "Timbang Masuk (Tare): " . number_format($weight) . " Kg";
                    $deliveryOrder->update([
                        'notes' => $deliveryOrder->notes ? $deliveryOrder->notes . " | " . $notes : $notes
                    ]);
                    $message = "Truk {$plate} ditimbang kosong. TARE WEIGHT: " . number_format($weight) . " Kg.";
                }
                break;

            case 'bay_loading':
                if (!in_array($deliveryOrder->status, ['draft', 'picking'])) {
                    $status = 'error';
                    $message = "Gagal memanggil ke Bay: Status DO #{$deliveryOrder->do_number} tidak valid (" . strtoupper($deliveryOrder->status) . ").";
                    $transitionSuccess = false;
                } else {
                    $bay = $request->loading_bay ?? 'Bay 1 Slitting';
                    $deliveryOrder->update([
                        'status' => 'picking',
                        'loading_bay' => $bay,
                        'called_at' => now(),
                    ]);
                    $message = "Truk {$plate} dipanggil merapat ke {$bay} untuk proses pemuatan.";
                    
                    // Trigger WA notification
                    $phone = null;
                    if ($deliveryOrder->driver_user_id) {
                        $driver = \App\Models\User::with('employee')->find($deliveryOrder->driver_user_id);
                        if ($driver && $driver->employee && $driver->employee->phone) {
                            $phone = $driver->employee->phone;
                        }
                    }
                    if ($phone) {
                        try {
                            $waService = app(\App\Services\WhatsappBotService::class);
                            $waMsg = "PANGGILAN RFID: Silakan membawa armada {$plate} memasuki {$bay} untuk loading DO #{$deliveryOrder->do_number}.";
                            $waService->sendNotification($phone, $waMsg);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to send WA in RFID: " . $e->getMessage());
                        }
                    }
                }
                break;

            case 'weighbridge_out':
                if ($deliveryOrder->status !== 'picking') {
                    $status = 'error';
                    $message = "Gagal menimbang: Status DO #{$deliveryOrder->do_number} harus PICKING (sedang memuat).";
                    $transitionSuccess = false;
                } else {
                    $weight = $request->weight ?? 15400; // default gross
                    $notes = "Timbang Keluar (Gross): " . number_format($weight) . " Kg";
                    $deliveryOrder->update([
                        'status' => 'packed',
                        'notes' => $deliveryOrder->notes ? $deliveryOrder->notes . " | " . $notes : $notes
                    ]);
                    $message = "Truk {$plate} ditimbang muatan. GROSS WEIGHT: " . number_format($weight) . " Kg. Status diperbarui ke READY (Packed).";
                }
                break;

            case 'gate_exit':
                if ($deliveryOrder->status !== 'packed') {
                    $status = 'error';
                    $message = "Gagal keluar pabrik: Truk {$plate} belum ditimbang muatan / status belum READY (Packed).";
                    $transitionSuccess = false;
                } else {
                    $deliveryOrder->update([
                        'status' => 'shipped',
                        'delivered_at' => now()
                    ]);
                    $message = "Truk {$plate} Berhasil Keluar Pos Satpam. Status diperbarui ke SHIPPED (Dalam Perjalanan).";
                }
                break;
        }

        // Save scan log
        \App\Models\RfidScanLog::create([
            'delivery_order_id' => $deliveryOrder->id,
            'tag_id' => $tagId,
            'reader_id' => $request->reader_id,
            'simulated_weight' => $request->weight,
            'status' => $status,
            'message' => $message,
        ]);

        if ($transitionSuccess) {
            activity()
                ->performedOn($deliveryOrder)
                ->withProperties([
                    'reader_id' => $request->reader_id,
                    'tag_id' => $tagId,
                    'weight' => $request->weight,
                    'status' => $status
                ])
                ->log("RFID Scan Log: " . $message);
        }

        return back()->with($status === 'error' ? 'error' : 'success', $message);
    }

    public function craneIndex()
    {
        // 1. Fetch all warehouses with their active locations
        $warehouses = Warehouse::with(['locations' => function($q) {
            $q->where('is_active', true);
        }])->orderBy('name')->get();

        // 2. Fetch all inventory lots (steel coils / lots) with product and current location
        $lots = \App\Models\InventoryLot::with(['product', 'location'])
            ->where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Warehouse/CraneControl', [
            'warehouses' => $warehouses,
            'lots' => $lots,
        ]);
    }

    public function craneMove(Request $request)
    {
        $request->validate([
            'inventory_lot_id' => 'required|exists:inventory_lots,id',
            'location_id' => 'required|exists:locations,id',
        ]);

        $lot = \App\Models\InventoryLot::findOrFail($request->inventory_lot_id);
        $location = Location::findOrFail($request->location_id);
        
        $oldLocationId = $lot->location_id;
        $warehouseId = $location->warehouse_id;

        // If target location is different from current location
        if ($oldLocationId !== $location->id) {
            // Update lot location and warehouse
            $lot->update([
                'location_id' => $location->id,
                'warehouse_id' => $warehouseId
            ]);

            // Adjust stock counts in product_stocks table
            // 1. Decrement old location stock (if not null)
            if ($oldLocationId) {
                $oldStock = \App\Models\ProductStock::where([
                    'product_id' => $lot->product_id,
                    'warehouse_id' => $lot->warehouse_id, // old warehouse
                    'location_id' => $oldLocationId
                ])->first();

                if ($oldStock) {
                    $oldStock->decrement('qty_on_hand', $lot->qty);
                    
                    // Create StockMovement for old location subtraction
                    \App\Models\StockMovement::create([
                        'product_id' => $lot->product_id,
                        'warehouse_id' => $lot->warehouse_id,
                        'location_id' => $oldLocationId,
                        'qty' => -$lot->qty,
                        'balance_before' => $oldStock->qty_on_hand + $lot->qty,
                        'balance_after' => $oldStock->qty_on_hand,
                        'type' => 'transfer_out',
                        'notes' => "Crane RFID auto-putaway: Dikeluarkan dari lokasi Lama. Coil #{$lot->coil_number}",
                        'created_by' => auth()->id() ?? 1,
                    ]);
                }
            }

            // 2. Increment target location stock
            $newStock = \App\Models\ProductStock::firstOrCreate(
                [
                    'product_id' => $lot->product_id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $location->id
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 0
                ]
            );

            $balanceBefore = $newStock->qty_on_hand;
            $newStock->increment('qty_on_hand', $lot->qty);

            // Create StockMovement for target location addition
            \App\Models\StockMovement::create([
                'product_id' => $lot->product_id,
                'warehouse_id' => $warehouseId,
                'location_id' => $location->id,
                'qty' => $lot->qty,
                'balance_before' => $balanceBefore,
                'balance_after' => $newStock->qty_on_hand,
                'type' => 'transfer_in',
                'notes' => "Crane RFID auto-putaway: Dimasukkan ke lokasi Baru. Coil #{$lot->coil_number}",
                'created_by' => auth()->id() ?? 1,
            ]);

            // Save log scanner in rfid_scan_logs
            \App\Models\RfidScanLog::create([
                'delivery_order_id' => null,
                'tag_id' => "RFID-COIL-" . $lot->coil_number,
                'reader_id' => "READER-CRANE-01",
                'simulated_weight' => $lot->weight,
                'status' => 'success',
                'message' => "Crane RFID: Mendeteksi pemindahan Coil #{$lot->coil_number} ke lokasi {$location->name} (Auto-Putaway).",
            ]);

            activity()
                ->performedOn($lot)
                ->withProperties([
                    'coil_number' => $lot->coil_number,
                    'old_location' => $oldLocationId ? Location::find($oldLocationId)->name : 'N/A',
                    'new_location' => $location->name,
                    'weight' => $lot->weight
                ])
                ->log("Crane RFID secara otomatis memindahkan Coil #{$lot->coil_number} ke lokasi {$location->name}");

            return back()->with('success', "Coil #{$lot->coil_number} berhasil dipindahkan oleh Crane ke lokasi {$location->name}.");
        }

        return back()->with('warning', "Coil #{$lot->coil_number} sudah berada di lokasi {$location->name}.");
    }
}
