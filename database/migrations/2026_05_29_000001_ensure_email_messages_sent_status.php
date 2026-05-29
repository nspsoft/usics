<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_messages')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `email_messages` MODIFY COLUMN `status` ENUM('unread','read','archived','sent') NOT NULL DEFAULT 'unread'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('email_messages')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `email_messages` MODIFY COLUMN `status` ENUM('unread','read','archived') NOT NULL DEFAULT 'unread'");
    }
};

