<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LogisticsDashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        // 1. KPI Stats
        $activeShipments = DeliveryOrder::whereIn('status', ['shipped', 'packed'])->count();
        $availableVehicles = Vehicle::where('status', 'available')->where('is_active', true)->count();
        
        // Delayed = Status not delivered but delivery_date < today
        $delayedDeliveries = DeliveryOrder::whereNotIn('status', ['delivered', 'completed', 'cancelled'])
            ->where('delivery_date', '<', $today)
            ->count();

        // Avg Delivery Time (In days from SHIPPED to DELIVERED) - mockup for now if delivered_at missing
        $avgDeliveryTime = 1.8;

        $stats = [
            'active_shipments' => $activeShipments,
            'available_vehicles' => $availableVehicles,
            'delayed_deliveries' => $delayedDeliveries,
            'avg_delivery_time' => $avgDeliveryTime,
        ];

        // 2. Delivery Pipeline (Chart Data)
        $pipeline = [
            'draft' => DeliveryOrder::where('status', 'draft')->count(),
            'picking' => DeliveryOrder::where('status', 'picking')->count(),
            'packed' => DeliveryOrder::where('status', 'packed')->count(),
            'shipped' => DeliveryOrder::where('status', 'shipped')->count(),
            'delivered' => DeliveryOrder::where('status', 'delivered')->count(),
        ];

        // 3. Fleet Status
        $fleetStatus = Vehicle::where('is_active', true)->get()->map(fn($v) => [
            'id' => $v->id,
            'plate' => $v->license_plate,
            'type' => $v->vehicle_type,
            'status' => strtoupper($v->status),
            'driver' => $v->driver_name ?? 'NOT ASSIGNED',
            'last_update' => $v->updated_at->diffForHumans(),
        ]);

        // 4. Recent Deliveries
        $recentDeliveries = DeliveryOrder::with(['customer', 'vehicle'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($do) => [
                'id' => $do->id,
                'do_number' => $do->do_number,
                'customer' => $do->customer->name ?? 'N/A',
                'vehicle' => $do->vehicle->license_plate ?? 'N/A',
                'status' => $do->status,
                'date' => $do->delivery_date->format('d M Y'),
            ]);

        // 5. Shipment Trend (Last 7 Days)
        $shipmentTrend = DeliveryOrder::select(
                DB::raw("DATE(delivery_date) as date"),
                DB::raw('COUNT(*) as total')
            )
            ->where('delivery_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Logistics/Dashboard', [
            'stats' => $stats,
            'pipeline' => $pipeline,
            'fleet' => $fleetStatus,
            'recent' => $recentDeliveries,
            'trend' => $shipmentTrend,
        ]);
    }
}
