<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'ga_location_id',
        'ga_asset_id',
        'reporter_id',
        'assignee_id',
        'image_path',
        'pos_x',
        'pos_y',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'pos_x' => 'float',
        'pos_y' => 'float',
        'resolved_at' => 'datetime',
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

    public function gaAsset()
    {
        return $this->belongsTo(GaAsset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function logs()
    {
        return $this->hasMany(GaTicketLog::class)->orderBy('created_at', 'desc');
    }
}
