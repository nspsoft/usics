<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailMessage extends Model
{
    protected $fillable = [
        'message_id',
        'subject',
        'from_address',
        'from_name',
        'to_address',
        'body_html',
        'body_text',
        'status',
        'intent',
        'sentiment',
        'urgency_score',
        'ai_metadata',
        'customer_id',
        'email_date',
    ];

    protected $casts = [
        'ai_metadata' => 'array',
        'email_date' => 'datetime',
        'urgency_score' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class);
    }
}
