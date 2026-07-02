<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'coil_number',
        'heat_number',
        'mill_id',
        'mtc_document_id',
        'thickness',
        'width',
        'length',
        'weight',
        'qty',
        'status',
        'notes',
    ];

    protected $casts = [
        'thickness' => 'float',
        'width' => 'float',
        'length' => 'float',
        'weight' => 'float',
        'qty' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function mill()
    {
        return $this->belongsTo(Supplier::class, 'mill_id');
    }

    public function mtcDocument()
    {
        return $this->belongsTo(MtcDocument::class, 'mtc_document_id');
    }
}
