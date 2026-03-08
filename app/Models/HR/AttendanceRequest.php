<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $table = 'hr_attendance_requests';

    protected $fillable = [
        'employee_id',
        'type',
        'request_date',
        'request_time',
        'reason',
        'attachment_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
