<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_numberings')
            ->where('code', 'quotation')
            ->update([
                'prefix' => 'QUOT',
                'format' => '{NUMBER}/QUOT/JRI-{CUST_CODE}/{ROMAN_MONTH}/{y}',
                'padding' => 3,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('document_numberings')
            ->where('code', 'quotation')
            ->update([
                'prefix' => 'QT',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
                'updated_at' => now(),
            ]);
    }
};

