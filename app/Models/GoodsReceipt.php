<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GoodsReceipt extends Model
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
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'delivery_note_number',
        'status',
        'supplier_invoice',
        'invoice_date',
        'notes',
        'received_by',
        'driver_name',
        'truck_number',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'invoice_date' => 'date',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSPECTED = 'inspected';
    const STATUS_COMPLETED = 'completed';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Generate GRN number
     */
    public static function generateGrnNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "GRN-{$year}{$month}-";
        
        $lastGrn = static::where('grn_number', 'like', "{$prefix}%")
            ->orderBy('grn_number', 'desc')
            ->first();

        if ($lastGrn) {
            $lastNumber = (int) substr($lastGrn->grn_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Complete goods receipt and update stock
     */
    public function complete(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return;
        }

        // Ensure items and their relations are loaded
        $this->load(['items.purchaseOrderItem', 'items.product', 'purchaseOrder.items']);

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $poItem = $item->purchaseOrderItem;
                if ($poItem) {
                    $poItem->qty_received += $item->qty_received;
                    $poItem->save();
                }

                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $this->warehouse_id,
                        'location_id' => $item->location_id,
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
                    $item->qty_received,
                    $item->unit_cost,
                    StockMovement::TYPE_PO_RECEIVE,
                    $this,
                    "Goods Receipt #{$this->grn_number}"
                );
            }

            $this->status = self::STATUS_COMPLETED;
            $this->save();

            $this->handleSubcontractAutoBackflush();

            $po = $this->purchaseOrder;
            if ($po) {
                $po->refresh();
                $allReceived = $po->items->every(fn($item) => $item->qty_received >= $item->qty - 0.0001);
                $someReceived = $po->items->some(fn($item) => $item->qty_received > 0);

                if ($allReceived) {
                    $po->status = PurchaseOrder::STATUS_RECEIVED;
                } elseif ($someReceived) {
                    $po->status = PurchaseOrder::STATUS_PARTIAL;
                }
                $po->save();
            }
        });
    }

    protected function handleSubcontractAutoBackflush(): void
    {
        if (!$this->purchase_order_id) {
            return;
        }

        $subcontractOrder = SubcontractOrder::query()
            ->with(['supplier.subcontractWarehouse', 'workOrder.components'])
            ->where('purchase_order_id', $this->purchase_order_id)
            ->first();

        if (!$subcontractOrder) {
            return;
        }

        $workOrder = $subcontractOrder->workOrder;
        if (!$workOrder) {
            return;
        }

        $subcontWarehouseId = $subcontractOrder->supplier?->subcontract_warehouse_id;
        if (!$subcontWarehouseId) {
            return;
        }

        $qtyReceived = (float) $this->items
            ->where('product_id', $workOrder->product_id)
            ->sum('qty_received');

        if ($qtyReceived <= 0) {
            return;
        }

        foreach ($workOrder->components as $component) {
            $usagePerUnit = $workOrder->qty_planned > 0 ? ((float) $component->qty_required / (float) $workOrder->qty_planned) : 0;
            $qtyToConsume = $usagePerUnit * $qtyReceived;

            if ($qtyToConsume <= 0) {
                continue;
            }

            $subcontStock = ProductStock::firstOrCreate(
                [
                    'product_id' => $component->product_id,
                    'warehouse_id' => $subcontWarehouseId,
                ],
                [
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 0,
                ]
            );

            $subcontStock->adjustStock(
                -$qtyToConsume,
                null,
                StockMovement::TYPE_PRODUCTION_OUT,
                $subcontractOrder,
                "Auto backflush from GR #{$this->grn_number} for WO: {$workOrder->wo_number} (Ref: {$subcontractOrder->order_number})",
                "GRN:{$this->grn_number}"
            );
        }

        if ($workOrder->status === WorkOrder::STATUS_CANCELLED) {
            return;
        }

        $totalProduced = (float) DB::table('goods_receipt_items as gri')
            ->join('goods_receipts as gr', 'gr.id', '=', 'gri.goods_receipt_id')
            ->where('gr.purchase_order_id', $this->purchase_order_id)
            ->where('gr.status', self::STATUS_COMPLETED)
            ->where('gri.product_id', $workOrder->product_id)
            ->sum('gri.qty_received');

        if ($totalProduced <= 0) {
            return;
        }

        if ($totalProduced >= (float) $workOrder->qty_planned) {
            $subcontractOrder->update(['status' => 'completed']);
            $workOrder->update([
                'qty_produced' => $totalProduced,
                'status' => WorkOrder::STATUS_COMPLETED,
                'actual_end' => now(),
                'actual_start' => $workOrder->actual_start ?? now(),
            ]);
            return;
        }

        $subcontractOrder->update(['status' => 'received']);
        $workOrder->update([
            'qty_produced' => $totalProduced,
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'actual_start' => $workOrder->actual_start ?? now(),
        ]);
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_DISPATCHED => 'orange',
            self::STATUS_RECEIVED => 'blue',
            self::STATUS_INSPECTED => 'amber',
            self::STATUS_COMPLETED => 'emerald',
            default => 'slate',
        };
    }
}
