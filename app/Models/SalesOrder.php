<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesOrder extends Model
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
        'so_number',
        'customer_po_number',
        'customer_id',
        'warehouse_id',
        'order_date',
        'delivery_date',
        'status',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'notes',
        'shipping_name',
        'shipping_address',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'order_date' => 'date:Y-m-d',
        'delivery_date' => 'date:Y-m-d',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'double',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'double',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'double',
        'total_amount' => 'double',
        'paid_amount' => 'double',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAITING_PO = 'waiting_po';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function coaDocuments(): HasMany
    {
        return $this->hasMany(CoaDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('subtotal');
        $discountAmount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * ($this->tax_percent / 100);
        $total = $afterDiscount + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_WAITING_PO]);
    }

    public function canDeliver(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_WAITING_PO]);
    }

    public static function generateSoNumber(): string
    {
        try {
            return app(\App\Services\DocumentNumberService::class)->generate('sales_order');
        } catch (\Exception $e) {
            // Fallback to old method if config missing (for safety)
            \Illuminate\Support\Facades\Log::warning("Document Numbering failing for sales_order: " . $e->getMessage());
            
            $year = date('Y');
            $month = date('m');
            $prefix = "SO-{$year}{$month}-";
            
            $last = static::where('so_number', 'like', "{$prefix}%")
                ->orderBy('so_number', 'desc')
                ->first();
    
            if ($last) {
                $lastNumber = (int) substr($last->so_number, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
    
            return $prefix . $newNumber;
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PROCESSING => 'amber',
            self::STATUS_SHIPPED => 'purple',
            self::STATUS_DELIVERED => 'emerald',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_WAITING_PO => 'orange',
            default => 'slate',
        };
    }
}
