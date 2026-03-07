<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            $table->date('transfer_date');
            $table->string('status', 20)->default('draft'); // draft, in_transit, received, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('transfer_date');
        });

        Schema::create('inv_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('inv_stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty_requested', 15, 4)->default(0);
            $table->decimal('qty_sent', 15, 4)->default(0);
            $table->decimal('qty_received', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_transfer_items');
        Schema::dropIfExists('inv_stock_transfers');
    }
};
