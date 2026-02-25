<?php

namespace App\Services;

use App\Models\DocumentNumbering;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentNumberService
{
    /**
     * Generate next document number
     * 
     * @param string $code Unique code for document type (e.g. 'sales_order')
     * @param array $params Optional parameters for dynamic placeholders (e.g. ['CUST_CODE' => 'ABC'])
     * @return string Generated number (e.g. 'SO/2026/01/0001')
     */
    public function generate(string $code, array $params = [], $date = null): string
    {
        return DB::transaction(function () use ($code, $params, $date) {
            $config = DocumentNumbering::where('code', $code)->lockForUpdate()->first();

            if (!$config) {
                // Fallback or throw error. For now, throw error to enforce configuration.
                throw new \Exception("Document numbering for '{$code}' not found.");
            }

            // Check reset period
            $this->checkResetPeriod($config);

            // Increment number
            $config->current_number++;
            $config->save();

            // Format number
            return $this->format($config, null, $params, $date);
        });
    }

    /**
     * Preview next number without incrementing
     */
    public function preview(string $code, array $params = [], $date = null): string
    {
        $config = DocumentNumbering::where('code', $code)->first();
        
        if (!$config) {
            return "NOT-CONFIGURED";
        }

        // Simulate next number
        $nextNumber = $config->current_number + 1;
        
        // Simulate checking reset (if reset needed, start from 1)
        if ($this->shouldReset($config)) {
            $nextNumber = 1;
        }

        return $this->format($config, $nextNumber, $params, $date);
    }

    protected function checkResetPeriod(DocumentNumbering $config): void
    {
        if ($this->shouldReset($config)) {
            $config->current_number = 0;
            $config->last_reset_date = now();
            // Note: We don't save here, saving happens after increment
        }
    }

    protected function shouldReset(DocumentNumbering $config): bool
    {
        if ($config->reset_period === 'never' || !$config->last_reset_date) {
            if (!$config->last_reset_date) {
                // First run, set date but don't reset number if it already has value
                // Or maybe we treat it as initialization. 
                // Logic: If no date, treat as today is the start.
                return false; 
            }
            return false;
        }

        $now = now();
        $lastReset = Carbon::parse($config->last_reset_date);

        return match ($config->reset_period) {
            'daily' => !$now->isSameDay($lastReset),
            'monthly' => !$now->isSameMonth($lastReset) || !$now->isSameYear($lastReset),
            'yearly' => !$now->isSameYear($lastReset),
            default => false,
        };
    }

    protected function format(DocumentNumbering $config, ?int $number = null, array $params = [], $date = null): string
    {
        $num = $number ?? $config->current_number;
        $paddedNumber = str_pad($num, $config->padding, '0', STR_PAD_LEFT);
        $dt = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : now();
        
        // Supported placeholders: {PREFIX}, {Y}, {y}, {m}, {d}, {NUMBER}
        $replacements = [
            '{PREFIX}' => $config->prefix,
            '{Y}' => $dt->format('Y'),
            '{y}' => $dt->format('y'),
            '{m}' => $dt->format('m'),
            '{d}' => $dt->format('d'),
            '{NUMBER}' => $paddedNumber,
        ];
        
        // Merge dynamic params
        foreach ($params as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $config->format);
    }
}
