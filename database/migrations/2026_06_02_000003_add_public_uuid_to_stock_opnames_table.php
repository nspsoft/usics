<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->uuid('public_uuid')->nullable()->after('opname_number');
            $table->unique('public_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('inv_stock_opnames', function (Blueprint $table) {
            $table->dropUnique(['public_uuid']);
            $table->dropColumn('public_uuid');
        });
    }
};

