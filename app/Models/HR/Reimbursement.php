<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Employee;

class Reimbursement extends Model
{
    use HasApproval, LogsActivity;

    protected $table = 'hr_reimbursements';
    
    protected $fillable = [
        'reimbursement_number', 'employee_id', 'date', 'type', 
        'amount', 'description', 'receipt_path', 'status', 'approval_status'
    ];
    
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
