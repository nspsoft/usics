<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'partner_type',
        'partner_id',
        'alias_sku',
        'alias_name',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function partner(): MorphTo
    {
        return $this->morphTo();
    }
}
