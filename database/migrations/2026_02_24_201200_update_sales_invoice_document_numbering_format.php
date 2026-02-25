<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $roman = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $monthRoman = $roman[(int) $now->format('n')] ?? 'I';
        $yearShort = $now->format('y');

        $max = null;
        if (DB::connection()->getDriverName() === 'mysql') {
            $like = "%/INV/JRI-%/{$monthRoman}/{$yearShort}";
            $max = DB::table('sales_invoices')
                ->where('invoice_number', 'like', $like)
                ->selectRaw('MAX(CAST(SUBSTRING_INDEX(invoice_number, "/", 1) AS UNSIGNED)) as max_num')
                ->value('max_num');
        }

        DB::table('document_numberings')
            ->where('code', 'sales_invoice')
            ->update([
                'prefix' => 'INV',
                'format' => '{NUMBER}/INV/JRI-{CUST_CODE}/{ROMAN_MONTH}/{y}',
                'padding' => 4,
                'reset_period' => 'monthly',
                'last_reset_date' => Carbon::parse($now->format('Y-m-01'))->toDateString(),
                'current_number' => (int) ($max ?? 0),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('document_numberings')
            ->where('code', 'sales_invoice')
            ->update([
                'prefix' => 'INV',
                'format' => '{PREFIX}/{y}{m}-{NUMBER}',
                'padding' => 3,
                'reset_period' => 'monthly',
                'updated_at' => now(),
            ]);
    }
};

