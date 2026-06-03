<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaVehicleTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'ga_vehicle_booking_id',
        'vehicle_id',
        'odometer_start',
        'odometer_end',
        'fuel_liters',
        'fuel_cost',
        'toll_cost',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
        'fuel_liters' => 'float',
        'fuel_cost' => 'decimal:2',
        'toll_cost' => 'decimal:2',
    ];

    protected $appends = ['receipt_url'];

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? '/storage/' . $this->receipt_path : null;
    }

    public function booking()
    {
        return $this->belongsTo(GaVehicleBooking::class, 'ga_vehicle_booking_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
