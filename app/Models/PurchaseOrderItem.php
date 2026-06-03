<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $appends = [
        'remaining_qty',
    ];

    protected $fillable = [
        'purchase_order_id',
        'work_order_id',
        'product_id',
        'description',
        'notes',
        'qty',
        'unit_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'qty_received',
        'qty_returned',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'discount_amount' => 'double',
        'subtotal' => 'double',
        'qty_received' => 'float',
        'qty_returned' => 'float',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQtyAttribute(): float
    {
        return $this->qty - ($this->qty_received - $this->qty_returned);
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->qty_received >= $this->qty;
    }

    /**
     * Calculate subtotal
     */
    public function calculateSubtotal(): void
    {
        $gross = $this->qty * $this->unit_price;
        $discountAmount = $gross * ($this->discount_percent / 100);
        $this->discount_amount = $discountAmount;
        $this->subtotal = $gross - $discountAmount;
        $this->save();
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item) {
            // Auto-calculate subtotal
            $gross = $item->qty * $item->unit_price;
            $discountAmount = $gross * ($item->discount_percent / 100);
            $item->discount_amount = $discountAmount;
            $item->subtotal = $gross - $discountAmount;
        });

        static::saved(function (PurchaseOrderItem $item) {
            // Recalculate PO totals
            $item->purchaseOrder->calculateTotals();
        });

        static::deleted(function (PurchaseOrderItem $item) {
            // Recalculate PO totals
            $item->purchaseOrder->calculateTotals();
        });
    }
}
