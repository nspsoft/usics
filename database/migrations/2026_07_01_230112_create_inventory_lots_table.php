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
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('coil_number')->unique();
            $table->string('heat_number')->nullable();
            $table->foreignId('mill_id')->nullable()->constrained('suppliers');
            $table->decimal('thickness', 10, 4)->nullable(); // in mm
            $table->decimal('width', 10, 4)->nullable(); // in mm
            $table->decimal('length', 10, 4)->nullable(); // in meters
            $table->decimal('weight', 10, 4)->default(0); // in kg
            $table->decimal('qty', 10, 4)->default(0); // current physical qty
            $table->string('status')->default('available'); // available, reserved, consumed, scrapped
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};
