<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
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
        'sku',
        'name',
        'description',
        'barcode',
        'category_id',
        'customer_id',
        'supplier_id',
        'type',
        'product_type',
        'unit_id',
        'purchase_unit_id',
        'sales_unit_id',
        'cost_price',
        'selling_price',
        'cost_method',
        'min_stock',
        'max_stock',
        'reorder_point',
        'reorder_qty',
        'lead_time_days',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'image',
        'images',
        'is_manufactured',
        'is_purchased',
        'is_sold',
        'track_serial',
        'track_batch',
        'track_expiry',
        'is_active',
        'attributes',
        'notes',
    ];

    protected $appends = [
        'total_stock',
        'available_stock',
        'is_low_stock',
        'can_delete'
    ];

    protected $casts = [
        'cost_price' => 'double',
        'selling_price' => 'double',
        'min_stock' => 'float',
        'max_stock' => 'float',
        'reorder_point' => 'float',
        'reorder_qty' => 'float',
        'lead_time_days' => 'integer',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'images' => 'array',
        'attributes' => 'array',
        'is_manufactured' => 'boolean',
        'is_purchased' => 'boolean',
        'is_sold' => 'boolean',
        'track_serial' => 'boolean',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Product Types
    const TYPE_RAW_MATERIAL = 'raw_material';
    const TYPE_WIP = 'wip';
    const TYPE_FINISHED_GOOD = 'finished_good';
    const TYPE_SPARE_PART = 'spare_part';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sales_unit_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function qcMasterPoints(): HasMany
    {
        return $this->hasMany(QcMasterPoint::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\ProductPartner::class);
    }

    /**
     * Get alias for specific partner
     */
    public function getAliasFor(Model $partner): ?\App\Models\Inventory\ProductPartner
    {
        return $this->partners()
            ->where('partner_type', $partner->getMorphClass())
            ->where('partner_id', $partner->id)
            ->first();
    }

    /**
     * Get total stock across all warehouses
     */
    public function getTotalStockAttribute(): float
    {
        return $this->stocks->sum('qty_on_hand');
    }

    /**
     * Get available stock (on hand - reserved)
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->stocks->sum(function ($stock) {
            return $stock->qty_on_hand - $stock->qty_reserved;
        });
    }

    /**
     * Get stock in specific warehouse
     */
    public function getStockInWarehouse(int $warehouseId): float
    {
        return $this->stocks
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_on_hand');
    }

    /**
     * Check if stock is below reorder point
     */
    /**
     * Check if stock is below reorder point
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock <= $this->reorder_point;
    }

    /**
     * Scope to filter by product type
     */
    public function scopeOfProductType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    /**
     * Scope to get low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->whereRaw('qty_on_hand <= products.reorder_point');
        });
    }

    /**
     * Scope active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include products that are stock-managed
     */
    public function scopeStockManaged($query)
    {
        return $query->whereIn('type', ['product', 'consumable']);
    }

    /**
     * Check if product has any transaction history
     */
    public function hasTransactions(): bool
    {
        // Critical transaction tables
        $tables = [
            'sales_order_items',
            'purchase_order_items',
            'quotation_items',
            'stock_movements',
            'goods_receipt_items',
            'delivery_order_items',
            'stock_adjustment_items',
            'stock_opname_items',
            'work_orders',
            'work_order_components',
            'bom_components',
            'boms',
            'material_consumptions',
            'sales_return_items',
            'purchase_return_items',
            'rfq_items',
            'purchase_request_items',
            'sales_invoice_items',
            'purchase_invoice_items',
        ];

        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                if (\DB::table($table)->where('product_id', $this->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get usage summary for the product
     */
    public function getUsageSummary(): array
    {
        $summary = [];
        
        // Critical transaction tables with human readable names
        $tables = [
            'sales_order_items' => 'Sales Orders',
            'purchase_order_items' => 'Purchase Orders',
            'quotation_items' => 'Quotations',
            'stock_movements' => 'Stock Movements',
            'goods_receipt_items' => 'Goods Receipts',
            'delivery_order_items' => 'Delivery Orders',
            'stock_adjustment_items' => 'Stock Adjustments',
            'stock_opname_items' => 'Stock Opnames',
            'work_orders' => 'Work Orders',
            'work_order_components' => 'Work Order Components',
            'bom_components' => 'Bill of Materials',
            'material_consumptions' => 'Material Consumptions',
            'sales_return_items' => 'Sales Returns',
            'purchase_return_items' => 'Purchase Returns',
            'rfq_items' => 'RFQs',
            'purchase_request_items' => 'Purchase Requests',
            'sales_invoice_items' => 'Sales Invoices',
            'purchase_invoice_items' => 'Purchase Invoices',
        ];

        foreach ($tables as $table => $label) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $count = \DB::table($table)->where('product_id', $this->id)->count();
                if ($count > 0) {
                    $summary[] = [
                        'type' => $label,
                        'count' => $count
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * Determine if product can be deleted
     */
    public function getCanDeleteAttribute(): bool
    {
        // 1. Must have zero stock
        if ($this->total_stock > 0) {
            return false;
        }

        // 2. Must not have any transaction history
        return !$this->hasTransactions();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
