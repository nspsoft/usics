<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomComponent extends Model
{
    use HasFactory;

    protected $touches = ['bom'];

    protected $fillable = [
        'bom_id',
        'product_id',
        'qty',
        'unit_id',
        'scrap_rate',
        'type',
        'sequence',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'waste_percent' => 'float',
        'cost' => 'double',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get required qty including scrap
     */
    public function getRequiredQtyAttribute(): float
    {
        return $this->qty * (1 + ($this->scrap_rate / 100));
    }

    /**
     * Get component cost
     */
    public function getCostAttribute(): float
    {
        return $this->qty * $this->product->cost_price;
    }
}
