<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaAssetLog extends Model
{
    protected $fillable = ['ga_asset_id', 'action', 'notes', 'user_id'];

    public function asset()
    {
        return $this->belongsTo(GaAsset::class, 'ga_asset_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
