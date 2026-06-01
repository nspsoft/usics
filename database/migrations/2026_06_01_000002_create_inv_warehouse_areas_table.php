<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_warehouse_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_key', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'name_key']);
            $table->index(['warehouse_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_warehouse_areas');
    }
};

