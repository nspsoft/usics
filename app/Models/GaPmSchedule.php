<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaPmSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'ga_asset_id',
        'task_name',
        'description',
        'interval_days',
        'last_performed_at',
        'next_due_date',
        'assignee_id',
        'status',
    ];

    protected $casts = [
        'last_performed_at' => 'date',
        'next_due_date' => 'date',
    ];

    public function gaAsset()
    {
        return $this->belongsTo(GaAsset::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function pmLogs()
    {
        return $this->hasMany(GaPmLog::class)->orderBy('performed_at', 'desc');
    }
}
