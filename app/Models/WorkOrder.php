<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasApproval;

class WorkOrder extends Model
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
        'wo_number',
        'bom_id',
        'product_id',
        'sales_order_id',
        'warehouse_id',
        'material_warehouse_id',
        'qty_planned',
        'qty_produced',
        'qty_rejected',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'status',
        'priority',
        'notes',
        'created_by',
        'production_type',
        'supplier_id',
        'approval_status',
    ];

    protected $casts = [
        'qty_to_produce' => 'float',
        'qty_produced' => 'float',
        'qty_scrapped' => 'float',
        'planned_start' => 'date',
        'planned_end' => 'date',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'production_type' => 'string',
        'supplier_id' => 'integer',
        'material_warehouse_id' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function materialWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'material_warehouse_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(WorkOrderComponent::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(WorkOrderOutput::class);
    }

    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class);
    }

    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function subcontractOrders(): HasMany
    {
        return $this->hasMany(SubcontractOrder::class);
    }

    public static function generateWoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "WO-{$year}{$month}-";
        
        $last = static::where('wo_number', 'like', "{$prefix}%")
            ->orderBy('wo_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->wo_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Initialize work order components from BOM
     */
    public function initializeFromBom(): void
    {
        foreach ($this->bom->components as $bomComp) {
            $multiplier = $this->qty_planned / $this->bom->qty;
            
            $this->components()->create([
                'bom_component_id' => $bomComp->id,
                'product_id' => $bomComp->product_id,
                'qty_required' => $bomComp->required_qty * $multiplier,
                'unit_id' => $bomComp->unit_id,
            ]);
        }

        if ($this->bom->outputs()->count() > 0) {
            $multiplier = $this->qty_planned / $this->bom->qty;
            foreach ($this->bom->outputs as $bomOutput) {
                $this->outputs()->create([
                    'product_id' => $bomOutput->product_id,
                    'qty_planned' => $bomOutput->qty_ratio * $multiplier,
                    'qty_produced' => 0,
                    'weight_produced' => 0,
                ]);
            }
        }
    }

    /**
     * Start production
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'actual_start' => now(),
        ]);
    }

    /**
     * Complete work order and update product stock
     */
    public function complete(): void
    {
        if ($this->productionEntries()->whereNotNull('stock_posted_at')->exists()) {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'actual_end' => now(),
            ]);
            return;
        }

        $goodQty = (float) ($this->qty_produced ?? 0);
        $totalOutputQty = $this->outputs()->sum('qty_produced');
        
        if ($goodQty <= 0 && $totalOutputQty <= 0) {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'actual_end' => now(),
            ]);
            return;
        }

        // Calculate production cost
        $productionCost = $this->calculateProductionCost();

        if ($this->outputs()->count() > 0) {
            // Divergent Manufacturing (Multiple Outputs)
            $totalWeight = $this->outputs()->sum('weight_produced');
            
            foreach ($this->outputs as $woOutput) {
                $qty = (float) $woOutput->qty_produced;
                if ($qty <= 0) continue;

                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $woOutput->product_id,
                        'warehouse_id' => $this->warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                // Allocate cost by weight ratio if weights are provided, otherwise by qty ratio
                $weightRatio = ($totalWeight > 0 && $woOutput->weight_produced > 0)
                    ? ($woOutput->weight_produced / $totalWeight) 
                    : ($qty / $totalOutputQty);

                $allocatedCost = $productionCost * $weightRatio;

                $stock->adjustStock(
                    $qty,
                    $allocatedCost / $qty,
                    StockMovement::TYPE_PRODUCTION_OUT,
                    $this,
                    "Production Output (Slitting/Shearing) WO #{$this->wo_number}"
                );
            }
        } else {
            // Convergent Manufacturing (Single Output)
            if ($goodQty > 0) {
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $this->product_id,
                        'warehouse_id' => $this->warehouse_id,
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
                    $goodQty,
                    $productionCost / $goodQty,
                    StockMovement::TYPE_PRODUCTION_OUT,
                    $this,
                    "Production Output WO #{$this->wo_number}"
                );
            }
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end' => now(),
        ]);
    }

    /**
     * Calculate production cost
     */
    public function calculateProductionCost(): float
    {
        return $this->materialConsumptions->sum(function ($consumption) {
            return $consumption->qty * $consumption->product->cost_price;
        });
    }

    public function getRemainingQtyAttribute(): float
    {
        return $this->qty_planned - $this->qty_produced;
    }

    public function getProgressPercentAttribute(): float
    {
        return $this->qty_planned > 0 
            ? min(100, ($this->qty_produced / $this->qty_planned) * 100)
            : 0;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_IN_PROGRESS => 'amber',
            self::STATUS_COMPLETED => 'emerald',
            self::STATUS_CANCELLED => 'red',
            default => 'slate',
        };
    }

    public function updateApprovalStatus(string $status): void
    {
        $this->approval_status = $status;
        
        if ($status === 'approved') {
            $this->status = self::STATUS_CONFIRMED;
        } elseif ($status === 'rejected') {
            $this->status = self::STATUS_DRAFT;
        }
        
        $this->saveQuietly();
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'slate',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'amber',
            self::PRIORITY_URGENT => 'red',
            default => 'slate',
        };
    }
}
