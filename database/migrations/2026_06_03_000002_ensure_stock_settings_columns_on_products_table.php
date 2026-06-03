<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'min_stock')) {
                $table->decimal('min_stock', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'max_stock')) {
                $table->decimal('max_stock', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'reorder_point')) {
                $table->decimal('reorder_point', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'reorder_qty')) {
                $table->decimal('reorder_qty', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'lead_time_days')) {
                $table->integer('lead_time_days')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'lead_time_days')) {
                $table->dropColumn('lead_time_days');
            }
            if (Schema::hasColumn('products', 'reorder_qty')) {
                $table->dropColumn('reorder_qty');
            }
            if (Schema::hasColumn('products', 'reorder_point')) {
                $table->dropColumn('reorder_point');
            }
            if (Schema::hasColumn('products', 'max_stock')) {
                $table->dropColumn('max_stock');
            }
            if (Schema::hasColumn('products', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
        });
    }
};

