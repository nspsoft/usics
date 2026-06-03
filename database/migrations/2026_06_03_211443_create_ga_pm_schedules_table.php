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
        Schema::create('ga_pm_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_asset_id')->constrained('ga_assets')->cascadeOnDelete();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->integer('interval_days');
            $table->date('last_performed_at')->nullable();
            $table->date('next_due_date');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active'); // active, paused
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_pm_schedules');
    }
};
