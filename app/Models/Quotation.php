<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasApproval;

class Quotation extends Model
{
    use HasFactory, LogsActivity, HasApproval;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'number',
        'public_uuid',
        'customer_id',
        'quotation_date',
        'valid_until',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'total',
        'created_by',
        'revision',
        'approval_status',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'double',
        'discount' => 'double',
        'tax' => 'double',
        'total' => 'double',
    ];

    public function updateApprovalStatus(string $status): void
    {
        $this->approval_status = $status;
        
        if ($status === 'approved') {
            // Can add specific logic if needed when approved. Currently just keep as draft or approved status.
        }
        
        $this->saveQuietly();
    }

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->public_uuid)) {
                $quotation->public_uuid = (string) Str::uuid();
            }
        });
    }

    public static function generateNumber(?int $customerId = null, ?string $date = null): string
    {
        $customerCode = 'GEN'; // General/Default if no customer selected
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer && $customer->code) {
                // Ensure pure alphanumeric code, maybe uppercase
                $customerCode = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $customer->code));
            } elseif ($customer) {
                 // Fallback to first 3 letters of name if no code
                 $customerCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $customer->name), 0, 3));
            }
        }

        $timestamp = $date ? strtotime($date) : time();
        $monthRoman = self::getRomanCheck(date('n', $timestamp));
        $year = date('y', $timestamp);
        
        // Format: NNN/QUOT/JRI-{CUST}/{ROMAN}/{YY}
        $likePattern = "%/QUOT/JRI-%/{$monthRoman}/{$year}";

        $lastQuery = static::where('number', 'like', $likePattern);
        if (DB::connection()->getDriverName() === 'mysql') {
            $lastQuery->orderByRaw('CAST(SUBSTRING_INDEX(number, "/", 1) AS UNSIGNED) DESC');
        } else {
            $lastQuery->orderByDesc('id');
        }

        $last = $lastQuery->value('number');
        
        $sequence = 1;
        if ($last) {
            $parts = explode('/', $last);
            if (is_numeric($parts[0])) {
                $sequence = (int) $parts[0] + 1;
            }
        }

        return sprintf(
            "%03d/QUOT/JRI-%s/%s/%s",
            $sequence,
            $customerCode,
            $monthRoman,
            $year
        );
    }

    private static function getRomanCheck($month)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[(int)$month] ?? 'I';
    }

    public function calculateTotal()
    {
        $this->subtotal = $this->items()->sum('total_price');
        // Default tax 11% if not explicitly set
        if ($this->tax == 0) {
            $this->tax = $this->subtotal * 0.11;
        }
        $this->total = $this->subtotal - ($this->discount ?? 0) + ($this->tax ?? 0);
        $this->save();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
