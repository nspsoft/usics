<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('work_order_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('work_orders')
                ->nullOnDelete();

            $table->index('work_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex(['work_order_id']);
            $table->dropConstrainedForeignId('work_order_id');
        });
    }
};

