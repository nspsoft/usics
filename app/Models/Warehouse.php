<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Warehouse extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

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
        'address',
        'city',
        'phone',
        'email',
        'manager_id',
        'type',
        'is_default',
        'allow_negative_stock',
        'is_active',
        'grid_cols',
        'grid_rows',
        'map_background_path',
    ];

    protected $appends = ['map_background_url'];

    /**
     * Get the full URL to the map background image
     */
    public function getMapBackgroundUrlAttribute(): ?string
    {
        return $this->map_background_path 
            ? \Illuminate\Support\Facades\Storage::url($this->map_background_path) 
            : null;
    }

    protected $casts = [
        'is_default' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_PRODUCTION = 'production';
    const TYPE_TRANSIT = 'transit';
    const TYPE_SCRAP = 'scrap';
    const TYPE_SUBCONTRACT = 'subcontract';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(WarehouseArea::class);
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get total stock value in this warehouse
     */
    public function getTotalStockValueAttribute(): float
    {
        return $this->productStocks->sum(function ($stock) {
            return $stock->qty_on_hand * $stock->avg_cost;
        });
    }

    /**
     * Scope to get active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
