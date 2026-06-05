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
        Schema::table('machines', function (Blueprint $table) {
            $table->string('qr_code_uuid')->unique()->nullable()->after('code');
            $table->date('purchase_date')->nullable()->after('qr_code_uuid');
            $table->decimal('runtime_hours', 10, 2)->default(0.00)->after('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['qr_code_uuid', 'purchase_date', 'runtime_hours']);
        });
    }
};
