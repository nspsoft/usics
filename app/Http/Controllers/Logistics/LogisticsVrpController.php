<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Vehicle;
use App\Models\AppSetting;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogisticsVrpController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Show VRP Optimization Dashboard.
     */
    public function optimizeIndex(Request $request)
    {
        // Fetch unscheduled delivery orders (shipment_number is null and status in draft, picking, packed)
        $deliveryOrders = DeliveryOrder::with(['customer', 'items.product'])
            ->whereNull('shipment_number')
            ->whereIn('status', ['draft', 'picking', 'packed'])
            ->orderBy('delivery_date')
            ->get()
            ->map(function ($do) {
                // Calculate total weight of DO items
                $totalWeight = $do->items->sum(function ($item) {
                    return $item->qty_delivered * ($item->product->weight ?? 0);
                });

                return [
                    'id' => $do->id,
                    'do_number' => $do->do_number,
                    'customer_name' => $do->customer->name ?? 'Guest',
                    'address' => $do->shipping_address ?: ($do->customer->address ?? 'No Address'),
                    'latitude' => $do->customer->latitude ? (float)$do->customer->latitude : null,
                    'longitude' => $do->customer->longitude ? (float)$do->customer->longitude : null,
                    'weight' => (float)$totalWeight,
                    'status' => $do->status,
                    'delivery_date' => $do->delivery_date ? $do->delivery_date->format('Y-m-d') : null,
                ];
            });

        // Fetch available vehicles
        $vehicles = Vehicle::where('is_active', true)
            ->whereIn('usage_type', ['logistics', 'both'])
            ->where('status', 'available')
            ->get();

        // Get depot coordinates from system preferences (default to Spindo HQ/Jakarta if not set)
        $depotLat = (float) AppSetting::get('office_latitude', -6.2088);
        $depotLng = (float) AppSetting::get('office_longitude', 106.8456);

        return Inertia::render('Logistics/Planning/Optimize', [
            'deliveryOrders' => $deliveryOrders,
            'vehicles' => $vehicles,
            'depot' => [
                'latitude' => $depotLat,
                'longitude' => $depotLng,
                'name' => 'Main Office / Depot'
            ]
        ]);
    }

    /**
     * Run VRP AI Route Optimization.
     */
    public function runVrpOptimization(Request $request)
    {
        $request->validate([
            'delivery_order_ids' => 'required|array',
            'delivery_order_ids.*' => 'exists:delivery_orders,id',
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        // Get depot coordinates
        $depotLat = (float) AppSetting::get('office_latitude', -6.2088);
        $depotLng = (float) AppSetting::get('office_longitude', 106.8456);

        // Fetch selected vehicles
        $vehicles = Vehicle::whereIn('id', $request->vehicle_ids)->get()->map(function ($v) {
            return [
                'id' => $v->id,
                'license_plate' => $v->license_plate,
                'vehicle_type' => $v->vehicle_type,
                'capacity_weight' => (float)($v->capacity_weight ?: 1000), // default 1 ton
                'capacity_volume' => (float)$v->capacity_volume,
                'driver_name' => $v->driver_name,
            ];
        })->toArray();

        // Fetch selected DOs and calculate weight
        $deliveryOrders = DeliveryOrder::with(['customer', 'items.product'])
            ->whereIn('id', $request->delivery_order_ids)
            ->get()
            ->map(function ($do) {
                $totalWeight = $do->items->sum(function ($item) {
                    return $item->qty_delivered * ($item->product->weight ?? 0);
                });

                return [
                    'delivery_order_id' => $do->id,
                    'do_number' => $do->do_number,
                    'customer_name' => $do->customer->name ?? 'Guest',
                    'address' => $do->shipping_address ?: ($do->customer->address ?? 'No Address'),
                    'latitude' => $do->customer->latitude ? (float)$do->customer->latitude : null,
                    'longitude' => $do->customer->longitude ? (float)$do->customer->longitude : null,
                    'weight' => (float)$totalWeight,
                ];
            })->toArray();

        if (empty($vehicles) || empty($deliveryOrders)) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan atau pesanan pengiriman tidak valid.',
            ], 422);
        }

        // Call VRP solver
        $result = $this->gemini->solveVrp($vehicles, $deliveryOrders, $depotLat, $depotLng);

        if (!$result || !is_array($result)) {
            return response()->json([
                'success' => false,
                'message' => 'AI gagal memproses optimasi rute. Silakan coba beberapa saat lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Apply VRP Optimization results to database.
     */
    public function applyVrpOptimization(Request $request)
    {
        $request->validate([
            'shipments' => 'required|array',
            'shipments.*.vehicle_id' => 'required|exists:vehicles,id',
            'shipments.*.suggested_allowance' => 'nullable|numeric|min:0',
            'shipments.*.stops' => 'required|array',
            'shipments.*.stops.*.delivery_order_id' => 'required|exists:delivery_orders,id',
        ]);

        try {
            DB::beginTransaction();

            $todayStr = date('ymd');
            $todayCount = DeliveryOrder::where('shipment_number', 'like', "SHP-{$todayStr}-%")->distinct()->count('shipment_number');

            foreach ($request->shipments as $index => $shipment) {
                $sequence = str_pad($todayCount + $index + 1, 3, '0', STR_PAD_LEFT);
                $shipmentNumber = "SHP-{$todayStr}-{$sequence}";

                $vehicle = Vehicle::find($shipment['vehicle_id']);
                if (!$vehicle) continue;

                $driverName = $vehicle->driver_name;
                $driverUser = \App\Models\User::where('name', $driverName)->first();
                $driverUserId = $driverUser ? $driverUser->id : null;

                $travelAllowance = (float)($shipment['suggested_allowance'] ?? 0);
                $travelAllowanceStatus = $travelAllowance > 0 ? 'requested' : 'none';

                foreach ($shipment['stops'] as $stopIndex => $stop) {
                    $isPrimary = ($stopIndex === 0);
                    
                    DeliveryOrder::where('id', $stop['delivery_order_id'])->update([
                        'vehicle_id' => $vehicle->id,
                        'vehicle_number' => $vehicle->license_plate,
                        'driver_name' => $driverName,
                        'driver_user_id' => $driverUserId,
                        'shipment_number' => $shipmentNumber,
                        'travel_allowance' => $isPrimary ? $travelAllowance : 0,
                        'travel_allowance_notes' => $isPrimary ? "AI Auto-Optimized Route Allowance" : null,
                        'travel_allowance_status' => $isPrimary ? $travelAllowanceStatus : 'none',
                        'status' => 'packed',
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('logistics.planning')->with('success', 'Rute optimasi AI VRP berhasil diterapkan ke database.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Apply VRP Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menerapkan optimasi rute: ' . $e->getMessage());
        }
    }
}
