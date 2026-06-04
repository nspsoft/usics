<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OkrKeyResult extends Model
{
    use HasFactory;

    protected $table = 'hr_okr_key_results';

    protected $fillable = [
        'objective_id',
        'title',
        'target_value',
        'current_value',
    ];

    public function objective()
    {
        return $this->belongsTo(OkrObjective::class, 'objective_id');
    }

    protected static function booted()
    {
        static::saved(function ($model) {
            $model->objective->updateScore();
        });
        static::deleted(function ($model) {
            $model->objective->updateScore();
        });
    }
}
