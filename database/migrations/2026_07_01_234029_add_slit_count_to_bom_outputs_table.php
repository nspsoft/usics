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
        Schema::table('bom_outputs', function (Blueprint $table) {
            $table->integer('slit_count')->default(1)->after('qty_ratio')->comment('Used for slitting BOMs to specify number of cuts for this output product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_outputs', function (Blueprint $table) {
            $table->dropColumn('slit_count');
        });
    }
};
