<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtcItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'mtc_document_id',
        'product_id',
        'product_no',
        'heat_no',
        'size',
        'quantity',
        'weight_kg',
        'position',
        'yp_mpa',
        'ts_mpa',
        'el_percent',
        'yr_percent',
        'bend_test',
        'impact_test_data',
        'chemical_ladle',
        'chemical_product',
        'division',
        'compliance_status',
        'compliance_notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'yp_mpa' => 'decimal:2',
        'ts_mpa' => 'decimal:2',
        'el_percent' => 'decimal:2',
        'yr_percent' => 'decimal:2',
        'impact_test_data' => 'array',
        'chemical_ladle' => 'array',
        'chemical_product' => 'array',
    ];

    /**
     * Chemical elements tracked in MTC
     */
    public const CHEMICAL_ELEMENTS = [
        'C', 'Si', 'Mn', 'P', 'S', 'Cr', 'Ni', 'B', 'Cu', 'Mo', 'Nb', 'Ti', 'V', 'CEQ'
    ];

    /**
     * Get the parent MTC document.
     */
    public function document()
    {
        return $this->belongsTo(MtcDocument::class, 'mtc_document_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get compliance status color.
     */
    public function getComplianceColorAttribute(): string
    {
        return match ($this->compliance_status) {
            'pass' => 'emerald',
            'fail' => 'red',
            'warning' => 'amber',
            'unchecked' => 'slate',
            default => 'slate',
        };
    }

    /**
     * Get compliance status icon.
     */
    public function getComplianceIconAttribute(): string
    {
        return match ($this->compliance_status) {
            'pass' => 'fas fa-check-circle',
            'fail' => 'fas fa-times-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'unchecked' => 'fas fa-question-circle',
            default => 'fas fa-question-circle',
        };
    }

    /**
     * Get a specific chemical element value from ladle analysis.
     */
    public function getLadleElement(string $element): ?float
    {
        return $this->chemical_ladle[$element] ?? null;
    }

    /**
     * Get a specific chemical element value from product analysis.
     */
    public function getProductElement(string $element): ?float
    {
        return $this->chemical_product[$element] ?? null;
    }
}
