<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaAsset extends Model
{
    protected $fillable = [
        'asset_code', 'name', 'category', 'purchase_date', 'price',
        'condition', 'location', 'ga_location_id', 'pos_x', 'pos_y',
        'user_id', 'image_path', 'status'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'price' => 'decimal:2',
        'pos_x' => 'float',
        'pos_y' => 'float',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? '/storage/' . $this->image_path : null;
    }

    public function gaLocation()
    {
        return $this->belongsTo(GaLocation::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(GaAssetLog::class);
    }
}
