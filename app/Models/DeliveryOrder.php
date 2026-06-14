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

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'do_number',
        'public_uuid',
        'vehicle_id',
        'vehicle_number',
        'driver_name',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'delivery_date',
        'status',
        'invoice_status',
        'shipping_name',
        'shipping_address',
        'shipping_method',
        'tracking_number',
        'notes',
        'prepared_by',
        'delivered_by',
        'delivered_at',
        'driver_user_id',
        'revision',
        'shipment_number',
        'travel_allowance',
        'travel_allowance_notes',
        'travel_allowance_status',
        'odometer_start',
        'odometer_end',
        'real_fuel_cost',
        'real_toll_cost',
        'real_other_cost',
        'real_costs_receipt_path',
    ];

    protected $appends = [
        'invoice_status',
        'status_color',
        'real_costs_receipt_url',
    ];

    public function getRealCostsReceiptUrlAttribute()
    {
        return $this->real_costs_receipt_path ? asset('storage/' . $this->real_costs_receipt_path) : null;
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    protected $casts = [
        'delivery_date' => 'date:Y-m-d',
        'delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DeliveryOrder $order) {
            if (empty($order->public_uuid)) {
                $order->public_uuid = (string) Str::uuid();
            }
        });
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_PICKING = 'picking';
    const STATUS_PACKED = 'packed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered'; // Driver marks this
    const STATUS_COMPLETED = 'completed'; // Admin verifies this

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public static function generateDoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "DO-{$year}{$month}-";
        
        $last = static::where('do_number', 'like', "{$prefix}%")
            ->orderBy('do_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->do_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Complete delivery and update stock
     */
    public function complete(): void
    {


        // Update SO status
        $so = $this->salesOrder;
        $allDelivered = $so->items->every(fn($item) => $item->isFullyDelivered());
        $so->status = $allDelivered ? SalesOrder::STATUS_DELIVERED : SalesOrder::STATUS_PROCESSING;
        $so->save();

        $this->status = self::STATUS_COMPLETED;
        $this->delivered_at = now();
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_PICKING => 'amber',
            self::STATUS_PACKED => 'blue',
            self::STATUS_SHIPPED => 'purple',
            self::STATUS_DELIVERED => 'teal',
            self::STATUS_COMPLETED => 'emerald',
            default => 'slate',
        };
    }

    /**
     * Get the invoicing status of the delivery order.
     */
    public function getInvoiceStatusAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Fallback to calculation if column is empty
        if ($this->items->isEmpty()) {
            return 'pending';
        }

        $allInvoiced = $this->items->every(function ($item) {
            return $item->qty_invoiced >= $item->qty_delivered;
        });

        if ($allInvoiced) {
            return 'invoiced';
        }

        $anyInvoiced = $this->items->some(function ($item) {
            return $item->qty_invoiced > 0;
        });

        if ($anyInvoiced) {
            return 'partial';
        }

        return 'pending';
    }

    public function refreshInvoiceStatus()
    {
        $status = 'pending';
        
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            $status = 'pending';
        } else {
            $allInvoiced = $items->every(function ($item) {
                return $item->qty_invoiced >= $item->qty_delivered;
            });

            if ($allInvoiced) {
                $status = 'invoiced';
            } else {
                $anyInvoiced = $items->some(function ($item) {
                    return $item->qty_invoiced > 0;
                });

                if ($anyInvoiced) {
                    $status = 'partial';
                }
            }
        }

        $this->update(['invoice_status' => $status]);
        return $status;
    }

    /**
     * Scope a query to only include delivery orders with a specific invoice status.
     */
    public function scopeInvoiceStatus($query, $status)
    {
        if ($status === 'invoiced') {
            return $query->whereHas('items', function ($q) {
                $q->whereRaw('qty_invoiced >= qty_delivered');
            });
        } elseif ($status === 'pending') {
            return $query->whereHas('items', function ($q) {
                $q->whereRaw('qty_invoiced < qty_delivered');
            });
        }
        return $query;
    }
}
