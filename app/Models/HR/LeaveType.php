<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'hr_leave_types';
    protected $fillable = ['name', 'description', 'max_days', 'is_paid', 'requires_attachment', 'is_active'];
    
    protected $casts = [
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
        'max_days' => 'integer',
    ];
}
