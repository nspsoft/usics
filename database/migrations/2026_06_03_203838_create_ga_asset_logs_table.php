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
        Schema::create('ga_asset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ga_asset_id')->constrained('ga_assets')->cascadeOnDelete();
            $table->string('action'); // e.g., 'created', 'updated', 'borrowed', 'returned', 'maintenance', 'relocated'
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // user who performed action
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_asset_logs');
    }
};
