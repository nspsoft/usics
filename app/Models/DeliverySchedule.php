<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySchedule extends Model
{
    //
    protected $fillable = [
        'customer_id',
        'product_id',
        'delivery_date',
        'qty_scheduled',
        'po_number',
        'reference_number',
        'notes',
        'created_by',
        'sales_name',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'qty_scheduled' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
