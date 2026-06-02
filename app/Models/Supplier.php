<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
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
        'contact_person',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'fax',
        'email',
        'website',
        'tax_id',
        'npwp',
        'payment_terms',
        'payment_days',
        'credit_limit',
        'currency',
        'notes',
        'is_active',
        'subcontract_warehouse_id',
    ];

    protected $casts = [
        'payment_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subcontractWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'subcontract_warehouse_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function productAliases(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Inventory\ProductPartner::class, 'partner');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Scope active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSubcontractors($query)
    {
        return $query
            ->active()
            ->whereNotNull('subcontract_warehouse_id')
            ->whereHas('subcontractWarehouse');
    }
}
