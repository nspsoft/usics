<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAttachment extends Model
{
    protected $fillable = [
        'email_message_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'is_po',
    ];

    public function emailMessage()
    {
        return $this->belongsTo(EmailMessage::class);
    }
}
