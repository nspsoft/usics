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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->string('shipment_number', 30)->nullable()->index()->after('do_number');
            $table->decimal('travel_allowance', 15, 2)->default(0)->after('status');
            $table->text('travel_allowance_notes')->nullable()->after('travel_allowance');
            $table->string('travel_allowance_status', 20)->default('none')->after('travel_allowance_notes');
            $table->integer('odometer_start')->default(0)->after('travel_allowance_status');
            $table->integer('odometer_end')->default(0)->after('odometer_start');
            $table->decimal('real_fuel_cost', 15, 2)->default(0)->after('odometer_end');
            $table->decimal('real_toll_cost', 15, 2)->default(0)->after('real_fuel_cost');
            $table->decimal('real_other_cost', 15, 2)->default(0)->after('real_toll_cost');
            $table->string('real_costs_receipt_path')->nullable()->after('real_other_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipment_number',
                'travel_allowance',
                'travel_allowance_notes',
                'travel_allowance_status',
                'odometer_start',
                'odometer_end',
                'real_fuel_cost',
                'real_toll_cost',
                'real_other_cost',
                'real_costs_receipt_path'
            ]);
        });
    }
};
