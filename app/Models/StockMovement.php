<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_movements';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'qty',
        'balance_before',
        'balance_after',
        'type',
        'reference_type',
        'reference_id',
        'external_reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
    ];

    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_PO_RECEIVE = 'po_receive';
    const TYPE_SO_DELIVERY = 'so_delivery';
    const TYPE_PRODUCTION_IN = 'production_in';
    const TYPE_PRODUCTION_OUT = 'production_out';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RECLASS = 'reclass';
    const TYPE_OPNAME = 'opname';
    const TYPE_PURCHASE_RETURN = 'purchase_return';
    const TYPE_SALES_RETURN = 'sales_return';
    const TYPE_CORRECTION = 'correction';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->type));
    }
}
