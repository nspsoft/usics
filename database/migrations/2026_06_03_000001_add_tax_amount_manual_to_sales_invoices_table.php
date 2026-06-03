<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('tax_amount_manual', 15, 2)->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('tax_amount_manual');
        });
    }
};

