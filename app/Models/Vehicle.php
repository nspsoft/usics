<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'traccar_device_id',
        'license_plate',
        'vehicle_type',
        'brand',
        'capacity_weight',
        'capacity_volume',
        'driver_name',
        'status',
        'notes',
        'is_active',
        'driver_photo',
        'vehicle_photo',
        'stnk_number',
        'stnk_expiry',
        'kir_number',
        'kir_expiry',
        'model',
        'year',
        'chassis_number',
        'engine_number',
        'fuel_type',
        'ownership',
        'purchase_date',
        'purchase_price',
        'usage_type'
    ];

    protected $casts = [
        'traccar_device_id' => 'integer',
    ];

    protected $appends = ['driver_photo_url', 'vehicle_photo_url'];

    public function getDriverPhotoUrlAttribute()
    {
        return $this->driver_photo ? asset('storage/' . $this->driver_photo) : null;
    }

    public function getVehiclePhotoUrlAttribute()
    {
        return $this->vehicle_photo ? asset('storage/' . $this->vehicle_photo) : null;
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function gaBookings()
    {
        return $this->hasMany(GaVehicleBooking::class);
    }
}
