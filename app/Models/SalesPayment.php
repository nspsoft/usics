<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'payment_number',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'bank_name',
        'account_number',
        'attachment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'double',
        'payment_date' => 'date',
    ];

    // Appends for calculated attributes
    protected $appends = [
        'attachment_url',
        'method_label',
    ];

    // Payment method options
    const METHOD_TRANSFER = 'Transfer';
    const METHOD_CASH = 'Cash';
    const METHOD_CHEQUE = 'Cheque';

    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_TRANSFER => 'Transfer Bank',
            self::METHOD_CASH => 'Tunai',
            self::METHOD_CHEQUE => 'Cek/Giro',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate payment number: SPAY-YYYYMM-XXXX
     */
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "SPAY-{$year}{$month}-";

        $lastPayment = static::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Get payment method label
     */
    public function getMethodLabelAttribute(): string
    {
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get attachment URL helper
     */
    public function getAttachmentUrlAttribute()
    {
        return $this->attachment ? asset('storage/' . $this->attachment) : null;
    }
}
