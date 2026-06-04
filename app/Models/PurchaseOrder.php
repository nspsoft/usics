<?php

namespace App\Models;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasApproval;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'po_number',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_date',
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
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
        'revision',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'exchange_rate' => 'double',
        'subtotal' => 'double',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'double',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'double',
        'total' => 'double',
        'paid_amount' => 'double',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_ORDERED = 'ordered';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function subcontractOrder(): HasOne
    {
        return $this->hasOne(SubcontractOrder::class, 'purchase_order_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate totals from items
     */
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

    /**
     * Check if PO can be edited
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT, 
            self::STATUS_SUBMITTED, 
            self::STATUS_APPROVED, 
            self::STATUS_ORDERED, 
            self::STATUS_PARTIAL
        ]);
    }

    /**
     * Check if PO can be received
     */
    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ORDERED, self::STATUS_ACKNOWLEDGED, self::STATUS_PARTIAL]);
    }

    /**
     * Get remaining quantity to receive for all items
     */
    public function getRemainingQtyAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->qty - $item->qty_received;
        });
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_SUBMITTED => 'amber',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_ORDERED => 'purple',
            self::STATUS_ACKNOWLEDGED => 'teal',
            self::STATUS_REJECTED => 'rose',
            self::STATUS_PARTIAL => 'orange',
            self::STATUS_RECEIVED => 'emerald',
            self::STATUS_CANCELLED => 'red',
            default => 'slate',
        };
    }

    /**
     * Generate PO number
     */
    public static function generatePoNumber(?Supplier $supplier = null, $date = null): string
    {
        try {
            return app(\App\Services\DocumentNumberService::class)->generate(
                'purchase_order',
                ['SUPP_CODE' => $supplier?->code ?? ''],
                $date
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Document Numbering failing for purchase_order: " . $e->getMessage());
            
            $year = now()->format('Y');
            $month = now()->format('m');
            $prefix = "PO-{$year}{$month}-";
            
            $lastPo = static::where('po_number', 'like', "{$prefix}%")
                ->orderBy('po_number', 'desc')
                ->first();

            if ($lastPo) {
                $lastNumber = (int) substr($lastPo->po_number, -4);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            return $prefix . $newNumber;
        }
    }

    public static function previewPoNumber(?Supplier $supplier = null, $date = null): string
    {
        try {
            return app(\App\Services\DocumentNumberService::class)->preview(
                'purchase_order',
                ['SUPP_CODE' => $supplier?->code ?? ''],
                $date
            );
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
