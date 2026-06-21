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
            $table->decimal('total_sell_value', 18, 2)->default(0)->after('total_value');
            $table->decimal('total_profit_nominal', 18, 2)->default(0)->after('total_sell_value');
            $table->decimal('total_profit_percentage', 8, 4)->default(0)->after('total_profit_nominal');
        });

        Schema::table('inv_stock_reclassification_items', function (Blueprint $table) {
            $table->decimal('selling_price_per_unit', 18, 4)->default(0)->after('cost_per_unit');
            $table->decimal('total_sell', 18, 2)->default(0)->after('total_cost');
            $table->decimal('profit_nominal', 18, 2)->default(0)->after('total_sell');
            $table->decimal('profit_percentage', 8, 4)->default(0)->after('profit_nominal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inv_stock_reclassification_items', function (Blueprint $table) {
            $table->dropColumn([
                'selling_price_per_unit',
                'total_sell',
                'profit_nominal',
                'profit_percentage'
            ]);
        });

        Schema::table('inv_stock_reclassifications', function (Blueprint $table) {
            $table->dropColumn([
                'total_sell_value',
                'total_profit_nominal',
                'total_profit_percentage'
            ]);
        });
    }
};
