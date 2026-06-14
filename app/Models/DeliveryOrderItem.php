<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeliveryOrderItem extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['qty_ordered', 'qty_delivered', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'delivery_order_id',
        'sales_order_item_id',
        'product_id',
        'qty_ordered',
        'qty_delivered',
        'unit_id',
        'location_id',
        'batch_number',
        'notes',
        'is_loaded',
        'qty_invoiced',
    ];

    protected $casts = [
        'qty_ordered' => 'float',
        'qty_delivered' => 'float',
        'qty_invoiced' => 'float',
        'is_loaded' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($deliveryOrderItem) {
            // Skip validation if explicitly requested (flag) or if no strictly linked SO Item
            // (Direct DOs create SO Items first, so they should be linked)
            if (!$deliveryOrderItem->sales_order_item_id) {
                return;
            }

            // Ensure SO Item is loaded
            if (!$deliveryOrderItem->relationLoaded('salesOrderItem')) {
                $deliveryOrderItem->load('salesOrderItem');
            }
            $soItem = $deliveryOrderItem->salesOrderItem;
            
            // If SO Item missing (orphan), skip or error. Let's skip to avoid blocking cleanup.
            if (!$soItem) return;

            // Calculate REAL Total Delivered for this SO Item (Excluding current DO Item)
            // We sum all DO Items linked to this SO Item that are NOT Cancelled.
            $query = self::where('sales_order_item_id', $soItem->id)
                ->whereHas('deliveryOrder', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                });
            
            if ($deliveryOrderItem->exists) {
                $query->where('id', '!=', $deliveryOrderItem->id);
            }

            $alreadyDelivered = (float) $query->sum('qty_delivered');
            
            // Get Returned Qty
            // Handle specific case where returnItems relation might be empty or problematic
            $qtyReturned = 0;
            try {
                $qtyReturned = (float) $soItem->returnItems()->sum('qty');
            } catch (\Exception $e) {
                // Ignore return calculation errors to be safe, or log them
                \Illuminate\Support\Facades\Log::warning("Failed to calculate returns for validation: " . $e->getMessage());
            }

            $currentQty = (float) $deliveryOrderItem->qty_delivered;
            $netDelivered = ($alreadyDelivered + $currentQty) - $qtyReturned;
            
            // Allow a small float tolerance (e.g. 0.0001)
            // Check if Net Delivered > Ordered
            if ($netDelivered > ($soItem->qty + 0.001)) {
                $remaining = $soItem->qty - ($alreadyDelivered - $qtyReturned);
                $sku = $soItem->product->sku ?? 'N/A';
                
                throw new \Exception(
                    "Over Delivery Detected! Item: {$soItem->description} (SKU: {$sku}). " .
                    "Qty Order: " . number_format($soItem->qty, 2) . ", " .
                    "Already Delivered (Net): " . number_format($alreadyDelivered - $qtyReturned, 2) . ", " .
                    "Attempting to Deliver: " . number_format($currentQty, 2) . ". " .
                    "Max Allowed: " . number_format($remaining, 2)
                );
            }
        });
        static::saved(function ($deliveryOrderItem) {
            if ($deliveryOrderItem->salesOrderItem) {
                $deliveryOrderItem->salesOrderItem->recalculateTotals();
            }
        });

        static::deleted(function ($deliveryOrderItem) {
            if ($deliveryOrderItem->salesOrderItem) {
                $deliveryOrderItem->salesOrderItem->recalculateTotals();
            }
        });

    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    protected $appends = [];

    public function getCurrentStockAttribute(): float
    {
        if (!$this->deliveryOrder) {
            return 0;
        }

        $stock = ProductStock::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->deliveryOrder->warehouse_id)
            ->first();

        return $stock ? (float) $stock->qty_on_hand : 0;
    }

    public function recalculateInvoiced()
    {
        // Calculate Real Invoiced Qty for this DO Item
        // Match by Delivery Order ID and Sales Order Item ID
        $realInvoiced = SalesInvoiceItem::where('delivery_order_id', $this->delivery_order_id)
            ->where('sales_order_item_id', $this->sales_order_item_id)
            ->whereHas('salesInvoice', function($q) {
                // Filter out cancelled/deleted invoices if applicable
            })
            ->sum('qty');

        if (abs($this->qty_invoiced - $realInvoiced) > 0.001) {
            $this->qty_invoiced = $realInvoiced;
            $this->save();
        }
    }
}
