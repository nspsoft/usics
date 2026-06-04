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
        Schema::create('hr_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('reimbursement_number')->unique();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('date');
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('draft');
            $table->string('approval_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_reimbursements');
    }
};
