<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $table = 'hr_applicants';

    protected $fillable = [
        'job_posting_id',
        'name',
        'email',
        'phone',
        'resume_path',
        'parsed_skills',
        'match_score',
        'status',
    ];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }
}
