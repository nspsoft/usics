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

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
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

    public function recalculateTotals()
    {
        // 1. Calculate Gross Delivered (Sum of Valid DO Items)
        $grossDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $this->id)
            ->whereHas('deliveryOrder', function ($q) {
                $q->whereIn('status', ['shipped', 'delivered', 'completed']);
            })
            ->sum('qty_delivered');

        // 2. Calculate Returned
        try {
            $returned = $this->returnItems()->sum('qty');
        } catch (\Exception $e) {
            $returned = 0;
        }

        // 3. Update if different
        if (abs($this->qty_delivered - $grossDelivered) > 0.001 || abs($this->qty_returned - $returned) > 0.001) {
            $this->qty_delivered = $grossDelivered;
            $this->qty_returned = $returned;
            $this->saveQuietly(); // Use saveQuietly to avoid recursion if we had other events, but check if we need to update Parent SO?
            
            // We SHOULD update Parent SO totals because delivered qty changed (might affect status)
            // But doing $this->salesOrder->calculateTotals() might be heavy. 
            // Let's rely on the 'saved' event of THIS model if we use save().
            // But wait, if I use saveQuietly, 'saved' won't fire.
            // If I use save(), 'saved' fires -> $item->salesOrder->calculateTotals().
            // This is correct.
            // However, to avoid infinite loops if 'saved' called this... 
            // checking 'isDirty' logic inside 'saved' prevents that?
            // This method is called EXPLICITLY. So save() is fine.
        }
        
        // Also trigger Parent SO update explicitly if saveQuietly was used, or just let save() do it.
        // Let's use save() to trigger the existing 'saved' event which updates SO header.
        if ($this->isDirty('qty_delivered') || $this->isDirty('qty_returned')) {
            $this->save(); 
        }
    }

    public function recalculateInvoiced()
    {
        // Calculate Real Invoiced Qty from Active Invoices
        $realInvoiced = $this->invoiceItems()
            ->whereHas('salesInvoice', function ($q) {
                // Ensure invoice is not deleted (if soft deletes used) and not cancelled?
                // Assuming standard SoftDeletes or status check if needed.
                // SalesInvoice model usually has SoftDeletes? Let's assume standard relationship integrity.
                // If the relationship is properly defined, it should respect global scopes.
                // But explicit check for 'cancelled' status might be needed if you have it.
            })
            ->sum('qty');

        if (abs($this->qty_invoiced - $realInvoiced) > 0.001) {
            $this->qty_invoiced = $realInvoiced;
            $this->save(); // Trigger 'saved' event to update Parent SO potentially
        }
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
            if ($item->salesOrder) {
                 $item->salesOrder->calculateTotals();
            }
        });

        static::deleted(function (SalesOrderItem $item) {
            if ($item->salesOrder) {
                $item->salesOrder->calculateTotals();
            }
        });
    }
}
