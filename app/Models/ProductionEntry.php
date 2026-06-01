<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Added this line

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductionEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'work_order_id',
        'production_date',
        'shift',
        'qty_produced',
        'qty_rejected',
        'defect_category',
        'downtime_minutes',
        'start_time',
        'end_time',
        'machine_line',
        'notes',
        'produced_by',
        'operator_employee_id',
        'entry_user_id',
        'client_request_id',
    ];

    protected $casts = [
        'qty_produced' => 'float',
        'qty_scrapped' => 'float',
        'qty_rejected' => 'float',
        'downtime_minutes' => 'integer',
    ];

    // Shift constants
    const SHIFT_1 = '1';
    const SHIFT_2 = '2';
    const SHIFT_3 = '3';

    public static function getShiftOptions(): array
    {
        return \App\Models\Shift::where('is_active', true)
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    // Defect category constants
    const DEFECT_DIMENSIONAL = 'dimensional';
    const DEFECT_SURFACE = 'surface';
    const DEFECT_MATERIAL = 'material';
    const DEFECT_ASSEMBLY = 'assembly';
    const DEFECT_OTHER = 'other';

    public static function getMachineOptions(): array
    {
        return \App\Models\Machine::where('is_active', true)
            ->get()
            ->pluck('name', 'name') // We use name as value for now as the table currently stores string names
            ->toArray();
    }

    public static function getDefectCategories(): array
    {
        return [
            self::DEFECT_DIMENSIONAL => 'Dimensional Error',
            self::DEFECT_SURFACE => 'Surface Defect',
            self::DEFECT_MATERIAL => 'Material Defect',
            self::DEFECT_ASSEMBLY => 'Assembly Error',
            self::DEFECT_OTHER => 'Other',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function producedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    public function operatorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_employee_id');
    }

    public function entryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_user_id');
    }

    public function getGoodQtyAttribute(): float
    {
        return $this->qty_produced - $this->qty_rejected;
    }

    protected static function booted(): void
    {
        static::saved(function (ProductionEntry $entry) {
            // Update work order totals
            $wo = $entry->workOrder;
            $wo->qty_produced = $wo->productionEntries()->sum('qty_produced');
            $wo->qty_rejected = $wo->productionEntries()->sum('qty_rejected');
            $wo->save();
        });
    }
}
