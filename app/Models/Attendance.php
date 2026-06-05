<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'hr_attendances';
    protected $fillable = [
        'employee_id', 'date', 'clock_in', 'clock_out', 
        'status', 'late_minutes', 'early_leave_minutes', 'overtime_minutes',
        'location_lat', 'location_lng', 'note'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    protected $appends = ['time_in', 'time_out'];

    public function getTimeInAttribute()
    {
        return $this->clock_in ? $this->clock_in->format('H:i:s') : null;
    }

    public function getTimeOutAttribute()
    {
        return $this->clock_out ? $this->clock_out->format('H:i:s') : null;
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
