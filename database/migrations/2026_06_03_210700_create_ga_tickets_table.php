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
        Schema::create('ga_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // facility, cleaning, it_support, security, other
            $table->string('priority'); // low, medium, high, critical
            $table->string('status'); // open, in_progress, resolved, closed
            
            $table->foreignId('ga_location_id')->nullable()->constrained('ga_locations')->nullOnDelete();
            $table->foreignId('ga_asset_id')->nullable()->constrained('ga_assets')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('image_path')->nullable(); // photo of the issue
            $table->float('pos_x')->nullable(); // custom coordinate on the map
            $table->float('pos_y')->nullable(); // custom coordinate on the map
            
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_tickets');
    }
};
