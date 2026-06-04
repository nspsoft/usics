<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'work_orders',
        'subcontract_orders',
        'purchase_invoices',
        'purchase_returns',
        'purchase_payments',
        'delivery_orders',
        'sales_invoices',
        'sales_returns',
        'stock_transfers',
        'stock_adjustments',
        'payrolls',
        'coa_documents',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'approval_status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('approval_status', 20)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multiple_tables', function (Blueprint $table) {
            //
        });
    }
};
