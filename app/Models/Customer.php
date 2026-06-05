<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $appends = ['full_address', 'profile_photo_url'];

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'contact_person',
        'address',
        'latitude',
        'longitude',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'tax_id',
        'payment_terms',
        'payment_days',
        'credit_limit',
        'currency',
        'customer_type',
        'notes',
        'is_active',
        'profile_photo_path',
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

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function productAliases(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Inventory\ProductPartner::class, 'partner');
    }

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

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        return asset('storage/' . $this->profile_photo_path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
