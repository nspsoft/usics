<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_contact_labels', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('label');
            $table->string('color', 20)->default('slate');
            $table->timestamps();

            $table->unique(['phone', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_labels');
    }
};
