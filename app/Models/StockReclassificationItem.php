<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReclassificationItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_reclassification_items';

    protected $fillable = [
        'stock_reclassification_id',
        'source_product_id',
        'target_product_id',
        'unit_id',
        'qty',
        'cost_per_unit',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'cost_per_unit' => 'double',
        'total_cost' => 'double',
    ];

    public function reclassification(): BelongsTo
    {
        return $this->belongsTo(StockReclassification::class, 'stock_reclassification_id');
    }

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
