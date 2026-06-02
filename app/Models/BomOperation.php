<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $touches = ['bom'];

    protected $fillable = [
        'bom_id',
        'name',
        'sequence',
        'setup_time_mins',
        'processing_time_mins',
        'labor_cost',
        'machine_cost',
        'description',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'setup_time_mins' => 'integer',
        'processing_time_mins' => 'integer',
        'labor_cost' => 'decimal:2',
        'machine_cost' => 'decimal:2',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }
}
