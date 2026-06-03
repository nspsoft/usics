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
        Schema::create('ga_vehicle_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_vehicle_booking_id')->constrained('ga_vehicle_bookings')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->integer('odometer_start');
            $table->integer('odometer_end')->nullable();
            $table->float('fuel_liters')->nullable();
            $table->decimal('fuel_cost', 15, 2)->nullable();
            $table->decimal('toll_cost', 15, 2)->nullable();
            $table->string('receipt_path')->nullable(); // Photo of receipts
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_vehicle_trips');
    }
};
