<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'description',
        'amount',
        'type',
        'reference_number',
        'bank_name',
        'reconciled_at',
        'sales_payment_id',
        'purchase_payment_id',
        'created_by',
        'hash',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'double',
        'reconciled_at' => 'datetime',
    ];

    public function salesPayment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function purchasePayment(): BelongsTo
    {
        return $this->belongsTo(PurchasePayment::class, 'purchase_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope unreconciled transactions
     */
    public function scopeUnreconciled($query)
    {
        return $query->whereNull('reconciled_at');
    }

    /**
     * Scope reconciled transactions
     */
    public function scopeReconciled($query)
    {
        return $query->whereNotNull('reconciled_at');
    }

    /**
     * Generate unique transaction hash to prevent duplicates
     */
    public static function generateHash($date, $description, $amount, $type): string
    {
        $normalizedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
        $normalizedDesc = strtolower(trim((string)$description));
        $normalizedAmount = number_format((float)$amount, 2, '.', '');
        $normalizedType = strtoupper(trim((string)$type));

        return hash('sha256', "{$normalizedDate}|{$normalizedDesc}|{$normalizedAmount}|{$normalizedType}");
    }
}
