<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaLocation extends Model
{
    protected $fillable = ['name', 'description', 'map_background_path'];

    protected $appends = ['map_background_url'];

    public function getMapBackgroundUrlAttribute(): ?string
    {
        return $this->map_background_path ? '/storage/' . $this->map_background_path : null;
    }

    public function assets()
    {
        return $this->hasMany(GaAsset::class);
    }
}
