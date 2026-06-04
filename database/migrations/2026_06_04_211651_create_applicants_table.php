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
        Schema::create('hr_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('hr_job_postings')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('resume_path')->nullable();
            $table->text('parsed_skills')->nullable();
            $table->integer('match_score')->default(0);
            $table->string('status')->default('Applied'); // Applied, Interview, Hired, Rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_applicants');
    }
};
