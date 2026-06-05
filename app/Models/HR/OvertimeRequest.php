<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    protected $table = 'hr_overtime_requests';

    protected $fillable = [
        'employee_id',
        'type',
        'date',
        'start_time',
        'end_time',
        'requested_minutes',
        'approved_minutes',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    protected $casts = [
        'date' => 'date',
        'requested_minutes' => 'integer',
        'approved_minutes' => 'integer',
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
