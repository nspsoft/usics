<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $yearShort = $now->format('y');
        $month = $now->format('m');

        $max = null;
        if (DB::connection()->getDriverName() === 'mysql') {
            $like = "JRI-%/{$yearShort}/{$month}/PRCH/%";
            $max = DB::table('purchase_orders')
                ->where('po_number', 'like', $like)
                ->selectRaw('MAX(CAST(SUBSTRING_INDEX(po_number, "/", -1) AS UNSIGNED)) as max_num')
                ->value('max_num');
        }

        DB::table('document_numberings')->updateOrInsert(
            ['code' => 'purchase_order'],
            [
                'module' => 'purchasing',
                'name' => 'Purchase Order',
                'prefix' => 'PRCH',
                'format' => 'JRI-{SUPP_CODE}/{y}/{m}/PRCH/{NUMBER}',
                'padding' => 3,
                'reset_period' => 'monthly',
                'last_reset_date' => Carbon::parse($now->format('Y-m-01'))->toDateString(),
                'current_number' => (int) ($max ?? 0),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('document_numberings')
            ->where('code', 'purchase_order')
            ->update([
                'prefix' => 'PO',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
                'reset_period' => 'monthly',
                'updated_at' => now(),
            ]);
    }
};

