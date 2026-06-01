<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MaterialConsumption extends Model
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
        'work_order_component_id',
        'product_id',
        'warehouse_id',
        'location_id',
        'qty',
        'unit_id',
        'batch_number',
        'consumption_date',
        'consumed_by',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'double',
        'consumption_date' => 'date',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderComponent(): BelongsTo
    {
        return $this->belongsTo(WorkOrderComponent::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function consumedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }

    protected static function booted(): void
    {
        static::created(function (MaterialConsumption $consumption) {
            // Update WO component consumed qty
            $woComp = $consumption->workOrderComponent;
            $woComp->qty_consumed += $consumption->qty;
            $woComp->save();

            // Reduce stock
            $stock = ProductStock::firstOrCreate(
                [
                    'product_id' => $consumption->product_id,
                    'warehouse_id' => $consumption->warehouse_id,
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 0,
                ]
            );

            $stock->adjustStock(
                -$consumption->qty,
                null,
                StockMovement::TYPE_PRODUCTION_IN,
                $consumption->workOrder,
                "Material consumption for WO #{$consumption->workOrder->wo_number}"
            );
        });
    }
}
