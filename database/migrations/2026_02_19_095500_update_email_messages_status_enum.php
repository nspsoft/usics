<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DB::statement("ALTER TABLE email_messages MODIFY COLUMN status ENUM('unread', 'read', 'archived', 'sent') NOT NULL DEFAULT 'unread'");
        // Using raw statement for Enum modification as it's cleaner than doctrine/dbal for enums sometimes
        Schema::table('email_messages', function (Blueprint $table) {
             $table->enum('status', ['unread', 'read', 'archived', 'sent'])->default('unread')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread')->change();
        });
    }
};
