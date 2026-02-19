<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'qty_system',
        'qty_physic',
        'qty_difference',
    ];

    protected $casts = [
        'qty_system' => 'float',
        'qty_physic' => 'float',
        'qty_difference' => 'float',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
