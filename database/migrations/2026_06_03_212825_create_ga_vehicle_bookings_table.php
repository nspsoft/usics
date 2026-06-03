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
        Schema::create('ga_vehicle_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('purpose');
            $table->string('destination');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('passengers_count')->default(1);
            $table->string('status')->default('pending'); // pending, approved, rejected, active, completed, cancelled
            $table->string('driver_name')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_vehicle_bookings');
    }
};
