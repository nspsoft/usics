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

    /**
     * Automatically check chemical and mechanical parameters against grade standards
     */
    public function checkCompliance(?string $grade)
    {
        if (!$grade) {
            $this->compliance_status = 'unchecked';
            $this->compliance_notes = 'Tidak ada spesifikasi grade untuk pengecekan standar.';
            return;
        }

        $standards = [
            'SPHC' => [
                'C' => ['max' => 0.15],
                'Mn' => ['max' => 0.60],
                'P' => ['max' => 0.050],
                'S' => ['max' => 0.050],
                'Si' => ['max' => 0.30],
                'yp_mpa' => ['min' => 235],
                'ts_mpa' => ['min' => 270],
                'el_percent' => ['min' => 27],
            ],
            'SPCC' => [
                'C' => ['max' => 0.15],
                'Mn' => ['max' => 0.60],
                'P' => ['max' => 0.100],
                'S' => ['max' => 0.035],
                'Si' => ['max' => 0.05],
            ],
            'SGCC' => [
                'C' => ['max' => 0.15],
                'Mn' => ['max' => 0.60],
                'P' => ['max' => 0.05],
                'S' => ['max' => 0.05],
                'Si' => ['max' => 0.05],
            ],
            'API 5L' => [
                'C' => ['max' => 0.24],
                'Mn' => ['max' => 1.20],
                'P' => ['max' => 0.025],
                'S' => ['max' => 0.015],
                'yp_mpa' => ['min' => 245, 'max' => 450],
                'ts_mpa' => ['min' => 415],
                'el_percent' => ['min' => 23],
            ]
        ];

        // Fuzzy match grade name
        $matchedStandard = null;
        $normalizedGrade = strtoupper($grade);
        foreach ($standards as $stdGrade => $rules) {
            if (str_contains($normalizedGrade, $stdGrade) || str_contains($stdGrade, $normalizedGrade)) {
                $matchedStandard = $rules;
                break;
            }
        }

        if (!$matchedStandard) {
            $this->compliance_status = 'unchecked';
            $this->compliance_notes = "Grade '{$grade}' tidak terdaftar dalam database standar.";
            return;
        }

        $failures = [];

        // Check chemical elements (ladle)
        $ladle = $this->chemical_ladle ?? [];
        foreach ($matchedStandard as $key => $limits) {
            // Chemical
            if (in_array($key, self::CHEMICAL_ELEMENTS)) {
                if (isset($ladle[$key]) && $ladle[$key] !== null && $ladle[$key] !== '') {
                    $val = (float)$ladle[$key];
                    if (isset($limits['min']) && $val < $limits['min']) {
                        $failures[] = "{$key} di bawah standar ({$val}% < {$limits['min']}%)";
                    }
                    if (isset($limits['max']) && $val > $limits['max']) {
                        $failures[] = "{$key} di atas standar ({$val}% > {$limits['max']}%)";
                    }
                }
            } else {
                // Mechanical properties (yp_mpa, ts_mpa, el_percent)
                $val = $this->{$key} ?? null;
                if ($val !== null && $val !== '' && is_numeric($val)) {
                    $val = (float)$val;
                    if (isset($limits['min']) && $val < $limits['min']) {
                        $failures[] = "{$key} di bawah standar ({$val} < {$limits['min']})";
                    }
                    if (isset($limits['max']) && $val > $limits['max']) {
                        $failures[] = "{$key} di atas standar ({$val} > {$limits['max']})";
                    }
                }
            }
        }

        if (empty($failures)) {
            $this->compliance_status = 'pass';
            $this->compliance_notes = 'Semua parameter memenuhi spesifikasi standar.';
        } else {
            $this->compliance_status = 'fail';
            $this->compliance_notes = implode('; ', $failures);
        }
    }
}
