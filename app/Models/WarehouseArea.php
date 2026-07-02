<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inv_warehouse_areas';

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'name_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

