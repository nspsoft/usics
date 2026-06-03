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
        Schema::table('meetings', function (Blueprint $table) {
            $table->longText('chairperson_signature')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('chairperson_signature');
            $table->string('signature_hash')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['chairperson_signature', 'approved_at', 'signature_hash']);
        });
    }
};
