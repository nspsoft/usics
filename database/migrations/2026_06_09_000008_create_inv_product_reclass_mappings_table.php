<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_product_reclass_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_product_id');
            $table->unsignedBigInteger('target_product_id');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('source_product_id');

            $table->foreign('source_product_id', 'inv_prm_source_fk')
                ->references('id')
                ->on('products');
            $table->foreign('target_product_id', 'inv_prm_target_fk')
                ->references('id')
                ->on('products');

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_product_reclass_mappings');
    }
};

