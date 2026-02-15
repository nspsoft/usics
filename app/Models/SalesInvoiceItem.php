<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'sales_order_item_id',
        'product_id',
        'description',
        'qty',
        'unit_id',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'delivery_order_id',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'double',
        'discount_percent' => 'float',
        'discount_amount' => 'double',
        'subtotal' => 'double',
        'delivery_order_id' => 'integer',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    protected static function booted(): void
    {
        static::saving(function (SalesInvoiceItem $item) {
            $gross = $item->qty * $item->unit_price;
            $discountAmount = $gross * ($item->discount_percent / 100);
            $item->discount_amount = $discountAmount;
            $item->subtotal = $gross - $discountAmount;
        });

        static::saved(function (SalesInvoiceItem $item) {
            // Trigger saving on invoice, which will call calculateTotals via its own saving event
            $item->salesInvoice->save();
        });
    }
}
