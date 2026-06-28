<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpdeskTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'title',
        'category',
        'priority',
        'status',
        'description',
        'url',
        'attachment_path',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(HelpdeskTicketReply::class, 'helpdesk_ticket_id')->orderBy('created_at', 'asc');
    }

    public static function generateTicketNumber(): string
    {
        $dateStr = date('Ym');
        $lastTicket = static::where('ticket_number', 'like', "TKT-{$dateStr}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastTicket) {
            $parts = explode('-', $lastTicket->ticket_number);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return 'TKT-' . $dateStr . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
