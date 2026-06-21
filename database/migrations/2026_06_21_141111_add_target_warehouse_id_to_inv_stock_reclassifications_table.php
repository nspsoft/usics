<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inv_stock_reclassifications', function (Blueprint $table) {
            $table->foreignId('target_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inv_stock_reclassifications', function (Blueprint $table) {
            $table->dropForeign(['target_warehouse_id']);
            $table->dropColumn('target_warehouse_id');
        });
    }
};
