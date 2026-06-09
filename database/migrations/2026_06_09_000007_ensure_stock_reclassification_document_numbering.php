<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_numberings')->updateOrInsert(
            ['code' => 'stock_reclassification'],
            [
                'module' => 'inventory',
                'name' => 'Stock Reclassification',
                'prefix' => 'RCL',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 4,
                'current_number' => 0,
                'reset_period' => 'monthly',
                'last_reset_date' => now()->toDateString(),
                'separator' => '/',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('code', 'stock_reclassification')->delete();
    }
};
