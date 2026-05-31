<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->string('location', 100)->nullable()->after('opname_date');
            $table->string('count_mode', 30)->default('full_input')->after('location');
        });

        Schema::table('inv_stock_opname_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('qty_difference');
        });
    }

    public function down(): void
    {
        Schema::table('inv_stock_opname_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->dropColumn(['location', 'count_mode']);
        });
    }
};

