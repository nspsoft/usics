<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\Sparepart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceDashboardController extends Controller
{
    public function index()
    {
        $machines = Machine::orderBy('name')->get();

        // 1. Calculate Machine List Telemetry
        $processedMachines = $machines->map(function ($machine) {
            $health = $machine->calculateHealthScore();
            $mtbf = $machine->getMtbfInDays();
            $predictedFailure = $machine->predictNextFailureDate();

            // Determine status
            $status = 'active';
            $activeBreakdown = $machine->logs()
                ->where('type', 'breakdown')
                ->whereIn('status', ['open', 'in_progress'])
                ->first();

            if ($activeBreakdown) {
                $status = 'breakdown';
            } else {
                $activePM = $machine->logs()
                    ->where('type', 'preventive')
                    ->whereIn('status', ['open', 'in_progress'])
                    ->first();
                if ($activePM) {
                    $status = 'maintenance';
                } elseif (!$machine->is_active) {
                    $status = 'inactive';
                }
            }

            return [
                'id' => $machine->id,
                'name' => $machine->name,
                'code' => $machine->code ?? '-',
                'qr_code_uuid' => $machine->qr_code_uuid,
                'purchase_date' => $machine->purchase_date ? $machine->purchase_date->format('Y-m-d') : '-',
                'runtime_hours' => (float) $machine->runtime_hours,
                'health_score' => $health,
                'mtbf' => $mtbf !== null ? $mtbf . ' hari' : 'Data tidak cukup',
                'predicted_failure' => $predictedFailure ? $predictedFailure->format('d M Y') : '-',
                'predicted_failure_raw' => $predictedFailure ? $predictedFailure->format('Y-m-d') : null,
                'status' => $status,
                'qr_url' => url("/m/{$machine->qr_code_uuid}/breakdown"),
                'tco' => $machine->calculateTco(),
            ];
        });

        // 2. Dashboard KPI Stats
        $avgHealth = $processedMachines->count() > 0 
            ? round($processedMachines->avg('health_score')) 
            : 100;

        $activeBreakdownsCount = MaintenanceLog::where('type', 'breakdown')
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $overduePMCount = MaintenanceSchedule::where('status', 'active')
            ->where('next_due_date', '<', Carbon::today())
            ->count();

        $criticalSparepartsCount = Sparepart::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // 3. Recent Breakdown Logs
        $recentBreakdowns = MaintenanceLog::with('machine')
            ->where('type', 'breakdown')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'machine_name' => $log->machine->name,
                    'machine_code' => $log->machine->code,
                    'description' => $log->description,
                    'started_at' => $log->started_at ? $log->started_at->format('d M H:i') : '-',
                    'status' => $log->status,
                    'technician' => $log->technician_name ?? '-',
                ];
            });

        // 4. Upcoming schedules
        $upcomingSchedules = MaintenanceSchedule::with('machine')
            ->where('status', 'active')
            ->orderBy('next_due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($sched) {
                $isOverdue = Carbon::parse($sched->next_due_date)->isPast();
                return [
                    'id' => $sched->id,
                    'machine_name' => $sched->machine->name,
                    'task_name' => $sched->task_name,
                    'next_due_date' => $sched->next_due_date ? $sched->next_due_date->format('d M Y') : '-',
                    'is_overdue' => $isOverdue,
                ];
            });

        return Inertia::render('Maintenance/Dashboard', [
            'stats' => [
                'average_health' => (int) $avgHealth,
                'active_breakdowns' => $activeBreakdownsCount,
                'overdue_pms' => $overduePMCount,
                'critical_spareparts' => $criticalSparepartsCount,
                'total_purchase_price' => (float) $machines->sum('purchase_price'),
                'total_spareparts_cost' => (float) $processedMachines->sum(fn($m) => $m['tco']['spareparts_cost']),
                'total_labor_cost' => (float) $processedMachines->sum(fn($m) => $m['tco']['labor_cost']),
                'total_tco_all' => (float) $processedMachines->sum(fn($m) => $m['tco']['total_tco']),
            ],
            'machines' => $processedMachines,
            'recent_breakdowns' => $recentBreakdowns,
            'upcoming_schedules' => $upcomingSchedules,
        ]);
    }
}
