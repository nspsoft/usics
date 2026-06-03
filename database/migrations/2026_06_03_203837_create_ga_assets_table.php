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
        Schema::create('ga_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('condition')->nullable();
            $table->string('location')->nullable(); // text descriptor
            $table->foreignId('ga_location_id')->nullable()->constrained('ga_locations')->nullOnDelete();
            $table->float('pos_x')->nullable();
            $table->float('pos_y')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // PIC
            $table->string('image_path')->nullable();
            $table->string('status')->default('active'); // active, maintenance, disposed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga_assets');
    }
};
