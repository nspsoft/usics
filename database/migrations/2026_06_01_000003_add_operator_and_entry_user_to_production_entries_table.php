<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->foreignId('operator_employee_id')
                ->nullable()
                ->after('produced_by')
                ->constrained('hr_employees')
                ->nullOnDelete();

            $table->foreignId('entry_user_id')
                ->nullable()
                ->after('operator_employee_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_user_id');
            $table->dropConstrainedForeignId('operator_employee_id');
        });
    }
};

