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
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->text('face_descriptor')->nullable()->after('profile_picture');
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->string('photo_in_path')->nullable()->after('note');
            $table->string('photo_out_path')->nullable()->after('photo_in_path');
            $table->boolean('is_face_verified')->default(false)->after('photo_out_path');
            $table->boolean('is_geofenced')->default(false)->after('is_face_verified');
            $table->decimal('distance_in_meters', 10, 2)->nullable()->after('is_geofenced');
            $table->decimal('distance_out_meters', 10, 2)->nullable()->after('distance_in_meters');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->decimal('office_latitude', 10, 8)->nullable();
            $table->decimal('office_longitude', 11, 8)->nullable();
            $table->integer('max_radius_meters')->default(50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('face_descriptor');
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'photo_in_path', 'photo_out_path', 'is_face_verified', 
                'is_geofenced', 'distance_in_meters', 'distance_out_meters'
            ]);
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['office_latitude', 'office_longitude', 'max_radius_meters']);
        });
    }
};
