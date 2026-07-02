<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'product_id',
        'qty_ratio',
        'slit_count',
        'unit_id',
        'notes',
    ];

    protected $casts = [
        'qty_ratio' => 'float',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
