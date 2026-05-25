<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'pr_number',
        'department',
        'requester',
        'request_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generatePrNumber()
    {
        // Format: PR/YYYY/MM/XXXX
        $prefix = 'PR/' . date('Y/m/');
        $lastPr = self::where('pr_number', 'like', $prefix . '%')
            ->orderBy('pr_number', 'desc')
            ->first();

        if (!$lastPr) {
            return $prefix . '0001';
        }

        $lastNumber = intval(substr($lastPr->pr_number, -4));
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
