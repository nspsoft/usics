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
        Schema::table('delivery_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_order_items', 'inchi')) {
                $table->string('inchi')->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('delivery_order_items', 'od')) {
                $table->decimal('od', 15, 4)->nullable()->after('inchi');
            }
            if (!Schema::hasColumn('delivery_order_items', 'tebal')) {
                $table->decimal('tebal', 15, 4)->nullable()->after('od');
            }
            if (!Schema::hasColumn('delivery_order_items', 'panjang')) {
                $table->decimal('panjang', 15, 4)->nullable()->after('tebal');
            }
            if (!Schema::hasColumn('delivery_order_items', 'kg_delivered')) {
                $table->decimal('kg_delivered', 15, 4)->nullable()->after('panjang');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $columns = [];
            foreach (['inchi', 'od', 'tebal', 'panjang', 'kg_delivered'] as $col) {
                if (Schema::hasColumn('delivery_order_items', $col)) {
                    $columns[] = $col;
                }
            }
            if (count($columns) > 0) {
                $table->dropColumn($columns);
            }
        });
    }
};
