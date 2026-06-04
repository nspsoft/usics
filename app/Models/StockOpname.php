<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockOpname extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'inv_stock_opnames';

    protected $fillable = [
        'warehouse_id',
        'opname_number',
        'public_uuid',
        'opname_date',
        'location',
        'count_mode',
        'status',
        'notes',
        'created_by',
        'checked_by',
        'checked_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockOpname $opname) {
            if (empty($opname->public_uuid)) {
                $opname->public_uuid = (string) Str::uuid();
            }
        });
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "SO-{$year}{$month}-";
        
        $last = static::withTrashed()
            ->where('opname_number', 'like', "{$prefix}%")
            ->orderBy('opname_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->opname_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}
