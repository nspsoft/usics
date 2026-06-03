<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingActionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'description',
        'pic_id',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}
