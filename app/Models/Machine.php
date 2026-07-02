<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Machine extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::creating(function ($machine) {
            if (empty($machine->qr_code_uuid)) {
                $machine->qr_code_uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'code',
        'type',
        'maker',
        'capacity',
        'qr_code_uuid',
        'purchase_date',
        'purchase_price',
        'runtime_hours',
        'is_active',
        'image_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'runtime_hours' => 'decimal:2',
    ];

    public function schedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /**
     * Calculate Machine Health Score (0 - 100)
     */
    public function calculateHealthScore(): int
    {
        $score = 100;

        // Penalty 1: Overdue PM Schedules (10 points each)
        $overdueCount = $this->schedules()
            ->where('status', 'active')
            ->where('next_due_date', '<', \Carbon\Carbon::today())
            ->count();
        $score -= $overdueCount * 10;

        // Penalty 2: Recent Breakdown Logs (15 points each in the last 30 days)
        $recentBreakdownsCount = $this->logs()
            ->where('type', 'breakdown')
            ->where('started_at', '>=', \Carbon\Carbon::now()->subDays(30))
            ->count();
        $score -= $recentBreakdownsCount * 15;

        // Penalty 3: Wear & tear based on runtime hours (1 point per 100 hours, max 30)
        $runtimeHours = (float) $this->runtime_hours;
        $runtimePenalty = min(30, floor($runtimeHours / 100));
        $score -= $runtimePenalty;

        return max(0, $score);
    }

    /**
     * Calculate MTBF (Mean Time Between Failures) in days
     */
    public function getMtbfInDays(): ?float
    {
        $breakdowns = $this->logs()
            ->where('type', 'breakdown')
            ->orderBy('started_at', 'asc')
            ->get();

        if ($breakdowns->count() < 2) {
            return null;
        }

        $totalDays = 0;
        for ($i = 1; $i < $breakdowns->count(); $i++) {
            $prev = \Carbon\Carbon::parse($breakdowns[$i - 1]->started_at);
            $curr = \Carbon\Carbon::parse($breakdowns[$i]->started_at);
            $totalDays += $prev->diffInDays($curr);
        }

        return round($totalDays / ($breakdowns->count() - 1), 1);
    }

    /**
     * Predict next failure date based on last failure and MTBF
     */
    public function predictNextFailureDate(): ?\Carbon\Carbon
    {
        $mtbf = $this->getMtbfInDays();
        if ($mtbf === null) {
            return null;
        }

        $lastBreakdown = $this->logs()
            ->where('type', 'breakdown')
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$lastBreakdown) {
            return null;
        }

        return \Carbon\Carbon::parse($lastBreakdown->started_at)->addDays((int) round($mtbf));
    }

    /**
     * Calculate Total Cost of Ownership (TCO) for this machine
     */
    public function calculateTco(): array
    {
        $purchasePrice = (float) ($this->purchase_price ?? 0.00);

        // 1. Calculate Spareparts Cost
        $sparepartsCost = \DB::table('maintenance_sparepart_usage')
            ->join('maintenance_logs', 'maintenance_sparepart_usage.maintenance_log_id', '=', 'maintenance_logs.id')
            ->join('spareparts', 'maintenance_sparepart_usage.sparepart_id', '=', 'spareparts.id')
            ->where('maintenance_logs.machine_id', $this->id)
            ->selectRaw('SUM(maintenance_sparepart_usage.qty_used * spareparts.unit_cost) as total')
            ->value('total') ?? 0.00;

        $sparepartsCost = (float) $sparepartsCost;

        // 2. Calculate Labor Cost (technician time)
        // Standard rate: IDR 50.000 / hour
        $laborRate = 50000;
        $resolvedLogs = $this->logs()
            ->where('status', 'resolved')
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->get();

        $laborCost = 0.00;
        foreach ($resolvedLogs as $log) {
            $start = \Carbon\Carbon::parse($log->started_at);
            $end = \Carbon\Carbon::parse($log->finished_at);
            $durationInHours = $start->diffInMinutes($end) / 60.0;
            $laborCost += $durationInHours * $laborRate;
        }

        $totalTco = $purchasePrice + $sparepartsCost + $laborCost;

        return [
            'purchase_price' => $purchasePrice,
            'spareparts_cost' => $sparepartsCost,
            'labor_cost' => round($laborCost, 2),
            'total_tco' => round($totalTco, 2),
        ];
    }
}
