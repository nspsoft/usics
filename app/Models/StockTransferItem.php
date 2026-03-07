<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'qty_requested',
        'qty_sent',
        'qty_received',
    ];

    protected $casts = [
        'qty_requested' => 'float',
        'qty_sent' => 'float',
        'qty_received' => 'float',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
