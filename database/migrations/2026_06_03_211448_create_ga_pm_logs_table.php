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
        Schema::create('ga_pm_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_pm_schedule_id')->constrained('ga_pm_schedules')->cascadeOnDelete();
            $table->date('performed_at');
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('technician_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_pm_logs');
    }
};
