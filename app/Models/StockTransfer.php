<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockTransfer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'inv_stock_transfers';

    protected $fillable = [
        'transfer_number',
        'source_warehouse_id',
        'destination_warehouse_id',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'received_by',
        'shipped_at',
        'received_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "TRF-{$year}{$month}-";

        $last = static::where('transfer_number', 'like', "{$prefix}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->transfer_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}
