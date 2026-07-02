<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'tag_id',
        'reader_id',
        'simulated_weight',
        'status',
        'message',
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
