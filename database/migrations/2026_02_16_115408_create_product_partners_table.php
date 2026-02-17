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
        Schema::create('product_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->morphs('partner'); // partner_type, partner_id
            $table->string('alias_sku')->nullable();
            $table->string('alias_name')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'partner_type', 'partner_id'], 'product_partner_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_partners');
    }
};
