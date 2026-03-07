<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'hr_leave_balances';
    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'total_days', 'used_days'];
    
    protected $casts = [
        'year' => 'integer',
        'total_days' => 'integer',
        'used_days' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
