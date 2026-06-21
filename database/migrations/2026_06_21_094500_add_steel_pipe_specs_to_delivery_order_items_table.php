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
            $table->string('inchi')->nullable()->after('unit_id');
            $table->decimal('od', 15, 4)->nullable()->after('inchi');
            $table->decimal('tebal', 15, 4)->nullable()->after('od');
            $table->decimal('panjang', 15, 4)->nullable()->after('tebal');
            $table->decimal('kg_delivered', 15, 4)->nullable()->after('panjang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn(['inchi', 'od', 'tebal', 'panjang', 'kg_delivered']);
        });
    }
};
