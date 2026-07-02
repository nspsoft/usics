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
        Schema::create('rfid_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
            $table->string('tag_id', 50);
            $table->string('reader_id', 50);
            $table->decimal('simulated_weight', 15, 2)->nullable();
            $table->enum('status', ['success', 'warning', 'error'])->default('success');
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_scan_logs');
    }
};
