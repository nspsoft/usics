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
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('profile_photo_path');
        });

        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->unsignedBigInteger('checked_by')->nullable()->after('created_by');
            $table->timestamp('checked_at')->nullable()->after('checked_by');
            $table->unsignedBigInteger('approved_by')->nullable()->after('checked_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('checked_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->dropForeign(['checked_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['checked_by', 'checked_at', 'approved_by', 'approved_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
