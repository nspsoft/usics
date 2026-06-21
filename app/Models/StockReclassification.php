<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockReclassification extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'inv_stock_reclassifications';

    protected $fillable = [
        'reclass_number',
        'warehouse_id',
        'target_warehouse_id',
        'reclass_date',
        'status',
        'reason',
        'notes',
        'total_qty',
        'total_value',
        'total_sell_value',
        'total_profit_nominal',
        'total_profit_percentage',
        'reference_type',
        'reference_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'reclass_date' => 'date',
        'total_qty' => 'float',
        'total_value' => 'double',
        'total_sell_value' => 'double',
        'total_profit_nominal' => 'double',
        'total_profit_percentage' => 'double',
        'posted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReclassificationItem::class, 'stock_reclassification_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public static function generateNumber($date = null): string
    {
        try {
            return app(DocumentNumberService::class)->preview('stock_reclassification', [], $date);
        } catch (\Throwable $e) {
            $dt = $date ? Carbon::parse($date) : now();
            $prefix = 'RCL/' . $dt->format('y/m') . '/';

            $last = static::where('reclass_number', 'like', $prefix . '%')
                ->orderBy('reclass_number', 'desc')
                ->first();

            $next = $last ? ((int) substr($last->reclass_number, -4)) + 1 : 1;

            return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        }
    }
}
