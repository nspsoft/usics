<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('pos_x')->nullable()->after('is_active');
            $table->integer('pos_y')->nullable()->after('pos_x');
            $table->integer('width')->default(1)->after('pos_y');
            $table->integer('height')->default(1)->after('width');
            $table->integer('capacity')->default(100)->after('height');
            $table->string('color', 20)->nullable()->after('capacity');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->integer('grid_cols')->default(12)->after('is_active');
            $table->integer('grid_rows')->default(8)->after('grid_cols');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y', 'width', 'height', 'capacity', 'color']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['grid_cols', 'grid_rows']);
        });
    }
};
