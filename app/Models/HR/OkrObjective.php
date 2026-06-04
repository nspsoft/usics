<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class OkrObjective extends Model
{
    use HasFactory;

    protected $table = 'hr_okr_objectives';

    protected $fillable = [
        'employee_id',
        'period',
        'title',
        'weight',
        'score',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function keyResults()
    {
        return $this->hasMany(OkrKeyResult::class, 'objective_id');
    }

    public function updateScore()
    {
        $krs = $this->keyResults;
        if ($krs->isEmpty()) {
            $this->update(['score' => 0]);
            return;
        }

        $totalScore = 0;
        foreach ($krs as $kr) {
            if ($kr->target_value > 0) {
                $percentage = ($kr->current_value / $kr->target_value) * 100;
                $percentage = min(100, max(0, $percentage)); // Cap at 100%
                $totalScore += $percentage;
            }
        }

        $averageScore = $totalScore / $krs->count();
        $this->update(['score' => $averageScore]);
    }
}
