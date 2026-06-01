<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->string('client_request_id', 64)->nullable()->after('entry_user_id');
            $table->unique('client_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropUnique(['client_request_id']);
            $table->dropColumn('client_request_id');
        });
    }
};

