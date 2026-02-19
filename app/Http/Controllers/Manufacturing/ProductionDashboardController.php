<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\ProductionEntry;
use App\Models\Shift;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductionDashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. OEE & Metrics
        $todayProduction = ProductionEntry::whereDate('production_date', $today)->sum('qty_produced');
        $yesterdayProduction = ProductionEntry::whereDate('production_date', $yesterday)->sum('qty_produced');
        $productionGrowth = $yesterdayProduction > 0 ? (($todayProduction - $yesterdayProduction) / $yesterdayProduction) * 100 : 0;

        // Quality Rate
        $totalProducedAll = ProductionEntry::sum('qty_produced');
        $totalRejectedAll = ProductionEntry::sum('qty_rejected');
        $qualityRate = $totalProducedAll > 0 ? (($totalProducedAll - $totalRejectedAll) / $totalProducedAll) * 100 : 98.4; // Fallback to mockup value

        // Performance (Actual vs Plan)
        $activeWOs = WorkOrder::whereIn('status', ['confirmed', 'in_progress'])->get();
        $totalPlanned = $activeWOs->sum('qty_planned');
        $totalProduced = $activeWOs->sum('qty_produced');
        $performanceRate = $totalPlanned > 0 ? ($totalProduced / $totalPlanned) * 100 : 85.0;

        // Availability (Run Time vs Downtime)
        $machineCount = Machine::where('is_active', true)->count();
        $totalScheduledMinutes = max($machineCount, 1) * 24 * 60;
        $totalDowntimeToday = ProductionEntry::whereDate('production_date', $today)->sum('downtime_minutes');
        $availabilityRate = $totalScheduledMinutes > 0 ? (($totalScheduledMinutes - $totalDowntimeToday) / $totalScheduledMinutes) * 100 : 91.0;

        $oeeValue = ($qualityRate / 100) * ($performanceRate / 100) * ($availabilityRate / 100) * 100;

        // 2. Shift Productivity (Today)
        $shifts = Shift::where('is_active', true)->get();
        $shiftProductivity = ProductionEntry::whereDate('production_date', $today)
            ->select('shift', DB::raw('SUM(qty_produced) as total_qty'))
            ->groupBy('shift')
            ->get()
            ->map(function($item) use ($shifts) {
                $shiftName = $shifts->firstWhere('id', $item->shift)->name ?? "Shift " . $item->shift;
                return [
                    'name' => $shiftName,
                    'output' => (float)$item->total_qty,
                    'target' => 500, // Example target
                ];
            });

        // 3. Machine Status
        $machines = Machine::where('is_active', true)->get()->map(function ($machine) use ($today) {
            $latestEntry = ProductionEntry::where('machine_line', $machine->name)
                ->whereDate('production_date', $today)
                ->latest()
                ->first();
            
            $status = 'IDLE';
            if ($latestEntry) {
                $status = $latestEntry->downtime_minutes > 0 ? 'DOWNTIME' : 'RUNNING';
            }

            return [
                'id' => $machine->id,
                'name' => $machine->name,
                'status' => $status,
                'last_qty' => $latestEntry->qty_produced ?? 0,
                'last_update' => $latestEntry ? $latestEntry->created_at->format('H:i') : '-',
            ];
        });

        // 4. Recent Production Logs
        $recentLogs = ProductionEntry::with(['workOrder.product'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'time' => $e->created_at->format('H:i'),
                'work_order' => $e->workOrder->wo_number ?? 'N/A',
                'product' => $e->workOrder->product->name ?? 'Unknown',
                'machine' => $e->machine_line ?? '-',
                'shift' => $e->shift,
                'qty' => $e->qty_produced,
                'rejects' => $e->qty_rejected,
            ]);

        // 5. Output Trend (Last 7 Days)
        $outputTrend = ProductionEntry::select(
                DB::raw("DATE(production_date) as date"),
                DB::raw('SUM(qty_produced) as total')
            )
            ->where('production_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Manufacturing/Dashboard', [
            'stats' => [
                'oee' => round($oeeValue, 1),
                'quality' => round($qualityRate, 1),
                'performance' => round($performanceRate, 1),
                'availability' => round($availabilityRate, 1),
                'today_qty' => (float)$todayProduction,
                'yesterday_qty' => (float)$yesterdayProduction,
                'growth' => round($productionGrowth, 1),
            ],
            'shift_data' => $shiftProductivity,
            'machine_statuses' => $machines,
            'recent_logs' => $recentLogs,
            'trend_data' => $outputTrend,
        ]);
    }
}
