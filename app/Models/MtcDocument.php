<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtcDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'file_name',
        'file_type',
        'supplier_id',
        'supplier_name',
        'certificate_number',
        'date_of_issue',
        'order_no',
        'po_no',
        'commodity',
        'spec_and_type',
        'customer',
        'raw_ai_response',
        'status',
        'verified_by',
        'verified_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'verified_at' => 'datetime',
        'raw_ai_response' => 'array',
    ];

    /**
     * Get the items (coils) for this MTC document.
     */
    public function items()
    {
        return $this->hasMany(MtcItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who verified this document.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for documents with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for draft documents.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Check if document is editable (only draft status).
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get the total weight of all items in this document.
     */
    public function getTotalWeightAttribute(): float
    {
        return $this->items->sum('weight_kg') ?? 0;
    }

    /**
     * Get the total quantity of all items.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity') ?? 0;
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'amber',
            'verified' => 'emerald',
            'pushed_to_sap' => 'blue',
            'rejected' => 'red',
            default => 'slate',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'verified' => 'Verified',
            'pushed_to_sap' => 'Pushed to SAP',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}
