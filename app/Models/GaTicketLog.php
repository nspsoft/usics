<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaTicketLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ga_ticket_id',
        'user_id',
        'action',
        'notes',
    ];

    public function ticket()
    {
        return $this->belongsTo(GaTicket::class, 'ga_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
