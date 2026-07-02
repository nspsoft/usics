<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'product_id',
        'qty_planned',
        'qty_produced',
        'weight_produced',
    ];

    protected $casts = [
        'qty_planned' => 'float',
        'qty_produced' => 'float',
        'weight_produced' => 'float',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
