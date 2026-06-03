<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesInvoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Generate custom invoice number: XXXX/INV/JRI-{CUST}/{ROMAN}/{YY}
     */
    public static function generateInvoiceNumber($customer, $date = null)
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();
        $year = $targetDate->format('y'); // 26
        $month = $targetDate->format('n'); // 1-12

        // Roman Numerals for months
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romans[$month];

        // Customer Code (Default to 'GEN' if empty)
        $custCode = $customer->code ?? 'GEN';

        try {
            return app(\App\Services\DocumentNumberService::class)->generate('sales_invoice', [
                'CUST_CODE' => $custCode,
                'ROMAN_MONTH' => $romanMonth
            ], $targetDate);
        } catch (\Exception $e) {
            $likePattern = "%/INV/JRI-%/{$romanMonth}/{$year}";
            $lastQuery = self::where('invoice_number', 'like', $likePattern);
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
                $lastQuery->orderByRaw('CAST(SUBSTRING_INDEX(invoice_number, "/", 1) AS UNSIGNED) DESC');
            } else {
                $lastQuery->orderByDesc('id');
            }
            $lastInvoiceNumber = $lastQuery->value('invoice_number');

            $nextSequence = 1;

            if ($lastInvoiceNumber) {
                $parts = explode('/', $lastInvoiceNumber);
                if (is_numeric($parts[0])) {
                    $nextSequence = (int) $parts[0] + 1;
                }
            }

            $sequenceStr = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            return "{$sequenceStr}/INV/JRI-{$custCode}/{$romanMonth}/{$year}";
        }
    }  

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $invoice) {
            if (empty($invoice->public_uuid)) {
                $invoice->public_uuid = (string) Str::uuid();
            }
        });

        static::saving(function (SalesInvoice $invoice) {
            $invoice->calculateTotals();
        });
    }

    /**
     * Get smart formatted DO numbers (e.g. "001, 002 /DO/JRI/...")
     */
    public function getFormattedDoNumbersAttribute(): string
    {
        // Get all unique DO numbers from items
        $doNumbers = $this->items->map(fn($item) => $item->deliveryOrder?->do_number)
            ->filter()
            ->unique()
            ->values();

        if ($doNumbers->isEmpty()) {
            return 'See Appendix';
        }

        // Group by suffix (everything after the first slash)
        $grouped = $doNumbers->groupBy(function ($doNum) {
            $parts = explode('/', $doNum, 2);
            return count($parts) > 1 ? '/' . $parts[1] : 'other';
        });

        $results = [];

        foreach ($grouped as $suffix => $numbers) {
            if ($suffix === 'other') {
                foreach ($numbers as $num) $results[] = $num;
            } else {
                // Extract prefixes (e.g. "010", "011")
                $prefixes = $numbers->map(function ($num) {
                    return explode('/', $num)[0];
                })->join(', ');
                
                $results[] = $prefixes . $suffix;
            }
        }

        return implode(', ', $results);
    }

    protected $fillable = [
        'company_id',
        'invoice_number',
        'public_uuid',
        'sales_order_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'tax_amount_manual',
        'total',
        'paid_amount',
        'balance',
        'notes',
        'created_by',
        'emeterai_serial',
        'emeterai_stamped_at',
        'emeterai_pdf_path',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'double',
        'tax_amount' => 'double',
        'tax_amount_manual' => 'double',
        'discount_amount' => 'double',
        'total' => 'double',
        'paid_amount' => 'double',
        'balance' => 'decimal:2',
        'emeterai_stamped_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }



    private static function getRomanMonth($month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[$month] ?? 'I';
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        // If SalesOrder has tax_percent, use it, otherwise default to 11
        $taxPercent = $this->salesOrder->tax_percent ?? 11;
        $this->tax_amount = $this->tax_amount_manual !== null
            ? (float) $this->tax_amount_manual
            : $this->subtotal * ($taxPercent / 100);
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance = $this->total - $this->paid_amount;
    }

    public function addPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance = $this->total - $this->paid_amount;
        
        if ($this->balance <= 0) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
        
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'slate',
            self::STATUS_SENT => 'blue',
            self::STATUS_PARTIAL => 'amber',
            self::STATUS_PAID => 'emerald',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'slate',
            default => 'slate',
        };
    }

    /**
     * Check if this invoice needs e-Meterai (total >= Rp 5.000.000 and not yet stamped).
     */
    public function needsEmeterai(): bool
    {
        return $this->total >= 5000000 && empty($this->emeterai_serial);
    }

    /**
     * Check if this invoice already has e-Meterai.
     */
    public function hasEmeterai(): bool
    {
        return !empty($this->emeterai_serial);
    }
}
