<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'type',
        'chairperson_id',
        'secretary_id',
        'discussion_notes',
        'status',
        'created_by',
        'chairperson_signature',
        'approved_at',
        'signature_hash',
    ];

    protected $casts = [
        'meeting_date' => 'date:Y-m-d',
        'approved_at' => 'datetime',
    ];

    public function chairperson()
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function secretary()
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function actionItems()
    {
        return $this->hasMany(MeetingActionItem::class);
    }
}
