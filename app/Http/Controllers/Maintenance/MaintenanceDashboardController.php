<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\Sparepart;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Run AI diagnostics for Predictive Maintenance.
     */
    public function runAiAdvisor(Request $request)
    {
        $machines = Machine::all()->map(function ($machine) {
            return [
                'id' => $machine->id,
                'name' => $machine->name,
                'code' => $machine->code,
                'runtime_hours' => (float)$machine->runtime_hours,
                'health_score' => $machine->calculateHealthScore(),
                'mtbf_days' => $machine->getMtbfInDays(),
                'predicted_failure_date' => $machine->predictNextFailureDate() ? $machine->predictNextFailureDate()->format('Y-m-d') : '-',
            ];
        })->toArray();

        $spareparts = Sparepart::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get()
            ->map(function ($part) {
                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'part_number' => $part->part_number,
                    'stock' => $part->stock,
                    'min_stock' => $part->min_stock,
                    'unit_cost' => (float)$part->unit_cost,
                ];
            })->toArray();

        $breakdowns = MaintenanceLog::with('machine')
            ->where('type', 'breakdown')
            ->where('started_at', '>=', Carbon::now()->subDays(60))
            ->orderBy('started_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($log) {
                $start = Carbon::parse($log->started_at);
                $end = $log->finished_at ? Carbon::parse($log->finished_at) : null;
                $duration = $end ? round($start->diffInMinutes($end) / 60, 1) : null;

                return [
                    'machine_name' => $log->machine->name ?? 'Unknown',
                    'description' => $log->description,
                    'duration_hours' => $duration,
                    'date' => $start->format('Y-m-d'),
                ];
            })->toArray();

        $gemini = app(GeminiService::class);
        $result = $gemini->analyzePredictiveMaintenance($machines, $spareparts, $breakdowns);

        if (!$result || !is_array($result)) {
            return response()->json([
                'success' => false,
                'message' => 'AI gagal melakukan diagnosis dan peramalan saat ini. Coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Create Consolidated Purchase Request from AI Recommendation.
     */
    public function createAiPr(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.part_number' => 'required|string',
            'items.*.recommended_qty' => 'required|numeric|min:1',
            'items.*.justification' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $prNumber = PurchaseRequest::generatePrNumber();

            $pr = PurchaseRequest::create([
                'company_id' => session('company_id') ?? 1,
                'pr_number' => $prNumber,
                'department' => 'Maintenance',
                'requester' => auth()->user()->name ?? 'System AI',
                'request_date' => Carbon::today(),
                'status' => 'draft',
                'notes' => "Consolidated Auto-PR generated via AI Predictive Maintenance Advisor.",
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $sparepart = Sparepart::where('part_number', $item['part_number'])->first();
                if (!$sparepart) {
                    throw new \Exception("Sparepart dengan part number {$item['part_number']} tidak ditemukan.");
                }

                // Find or create product mapping
                $product = Product::where('sku', $sparepart->part_number)->first();
                if (!$product) {
                    $category = Category::where('code', 'SP')->first() ?? Category::first();
                    $unit = Unit::where('code', 'PCS')->first() ?? Unit::first();
                    
                    $product = Product::create([
                        'company_id' => session('company_id') ?? 1,
                        'sku' => $sparepart->part_number,
                        'name' => $sparepart->name,
                        'category_id' => $category?->id,
                        'unit_id' => $unit?->id,
                        'product_type' => 'spare_part',
                        'cost_price' => $sparepart->unit_cost,
                        'is_purchased' => true,
                        'is_active' => true,
                    ]);
                }

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $product->id,
                    'qty' => (float)$item['recommended_qty'],
                    'description' => "Rekomendasi AI Suku Cadang: {$sparepart->name}. Justifikasi: " . ($item['justification'] ?? 'Auto-procurement'),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', "Draft Purchase Request {$prNumber} berhasil dibuat di modul Purchasing.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create AI PR Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat Purchase Request: ' . $e->getMessage());
        }
    }
}
