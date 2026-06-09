<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_reclassifications', function (Blueprint $table) {
            $table->id();
            $table->string('reclass_number', 30)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('reclass_date');
            $table->string('status', 20)->default('draft');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->decimal('total_qty', 15, 4)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('reclass_date');
            $table->index('warehouse_id');
        });

        Schema::create('inv_stock_reclassification_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_reclassification_id');
            $table->unsignedBigInteger('source_product_id');
            $table->unsignedBigInteger('target_product_id');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('qty', 15, 4);
            $table->decimal('cost_per_unit', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_reclassification_id', 'inv_sr_items_reclass_fk')
                ->references('id')
                ->on('inv_stock_reclassifications')
                ->cascadeOnDelete();
            $table->foreign('source_product_id', 'inv_sr_items_source_fk')
                ->references('id')
                ->on('products');
            $table->foreign('target_product_id', 'inv_sr_items_target_fk')
                ->references('id')
                ->on('products');
            $table->foreign('unit_id', 'inv_sr_items_unit_fk')
                ->references('id')
                ->on('units')
                ->nullOnDelete();

            $table->index('source_product_id');
            $table->index('target_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_reclassification_items');
        Schema::dropIfExists('inv_stock_reclassifications');
    }
};
