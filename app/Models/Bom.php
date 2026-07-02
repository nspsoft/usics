<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Bom extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'product_id',
        'qty',
        'unit_id',
        'version',
        'status',
        'description',
        'notes',
        'lead_time_days',
        'is_active',
    ];

    protected $casts = [
        'qty' => 'float',
        'cost' => 'double',
        'lead_time_days' => 'integer',
        'is_active' => 'boolean',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(BomComponent::class)->orderBy('sequence');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(BomOutput::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class)->orderBy('sequence');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Get total material cost
     */
    public function getTotalMaterialCostAttribute(): float
    {
        return $this->components->sum(function ($comp) {
            return $comp->qty * ($comp->product->cost_price ?? 0);
        });
    }

    /**
     * Get total labor cost
     */
    public function getTotalLaborCostAttribute(): float
    {
        return $this->operations->sum('labor_cost');
    }

    /**
     * Get total machine cost
     */
    public function getTotalMachineCostAttribute(): float
    {
        return $this->operations->sum('machine_cost');
    }

    /**
     * Get total standard cost (Materials + Labor + Machine)
     */
    public function getTotalCostAttribute(): float
    {
        return $this->total_material_cost + $this->total_labor_cost + $this->total_machine_cost;
    }

    /**
     * Get cost per unit
     */
    public function getCostPerUnitAttribute(): float
    {
        return $this->qty > 0 ? $this->total_cost / $this->qty : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', self::STATUS_ACTIVE);
    }
}
