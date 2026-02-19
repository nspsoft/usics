<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('metadata');
        });

        // Mark all existing messages as read
        \DB::table('whatsapp_messages')->update(['is_read' => true]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    }
};
