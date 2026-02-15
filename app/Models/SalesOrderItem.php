<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesOrderItem extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['qty', 'unit_price', 'discount_percent', 'subtotal'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'qty',
        'unit_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'qty_delivered',
        'qty_returned',
        'qty_invoiced',
    ];

    protected $appends = ['reserved_qty', 'remaining_qty'];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'discount_amount' => 'double',
        'subtotal' => 'double',
        'qty_delivered' => 'decimal:4',
        'qty_returned' => 'decimal:4',
        'qty_invoiced' => 'decimal:4',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function deliveryOrderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function returnItems()
    {
        return $this->hasManyThrough(
            SalesReturnItem::class,
            SalesReturn::class,
            'sales_order_id', // Foreign key on SalesReturn table...
            'sales_return_id', // Foreign key on SalesReturnItem table...
            'sales_order_id', // Local key on SalesOrderItem table...
            'id' // Local key on SalesReturn table...
        )->where('sales_return_items.product_id', $this->product_id);
    }

    public function getReservedQtyAttribute(): float
    {
        // Optimization: Use loaded relationship if available to prevent N+1 queries
        if ($this->relationLoaded('deliveryOrderItems')) {
            return (float) $this->deliveryOrderItems
                ->filter(function ($doItem) {
                    $status = $doItem->deliveryOrder?->status;
                    if (!$status) return false;
                    return !in_array($status, ['shipped', 'delivered', 'completed', 'cancelled']);
                })
                ->sum('qty_delivered');
        }

        return (float) $this->deliveryOrderItems()
            ->whereHas('deliveryOrder', function ($query) {
                // We count everything that is NOT delivered and NOT cancelled as "Reserved"
                // Delivered is already subtracted via qty_delivered
                $query->whereNotIn('status', ['shipped', 'delivered', 'completed', 'cancelled']);
            })
            ->sum('qty_delivered'); // qty_delivered in DO Item means "planned qty"
    }

    public function getRemainingQtyAttribute(): float
    {
        $deliveredNet = $this->qty_delivered - $this->qty_returned;
        return $this->qty - $deliveredNet - $this->reserved_qty;
    }

    public function isFullyDelivered(): bool
    {
        return $this->qty_delivered >= $this->qty;
    }

    protected static function booted(): void
    {
        static::saving(function (SalesOrderItem $item) {
            $gross = $item->qty * $item->unit_price;
            $discountAmount = $gross * ($item->discount_percent / 100);
            $item->discount_amount = $discountAmount;
            $item->subtotal = $gross - $discountAmount;
        });

        static::saved(function (SalesOrderItem $item) {
            $item->salesOrder->calculateTotals();
        });

        static::deleted(function (SalesOrderItem $item) {
            $item->salesOrder->calculateTotals();
        });
    }
}
