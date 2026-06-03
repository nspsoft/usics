<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaPmLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ga_pm_schedule_id',
        'performed_at',
        'performed_by_id',
        'technician_name',
        'notes',
        'cost',
    ];

    protected $casts = [
        'performed_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function pmSchedule()
    {
        return $this->belongsTo(GaPmSchedule::class, 'ga_pm_schedule_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
