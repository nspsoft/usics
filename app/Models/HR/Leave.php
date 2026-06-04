<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use \App\Traits\HasApproval;

    protected $table = 'hr_leaves';
    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 
        'total_days', 'reason', 'status', 'attachment_path', 
        'approved_by', 'approved_at', 'rejection_reason', 'approval_status'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
