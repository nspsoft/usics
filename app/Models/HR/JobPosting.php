<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Department;

class JobPosting extends Model
{
    use HasFactory;

    protected $table = 'hr_job_postings';
    
    protected $fillable = [
        'title',
        'department_id',
        'description',
        'requirements',
        'status',
        'closing_date',
    ];

    protected $casts = [
        'closing_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
