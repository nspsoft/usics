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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('customers', 'account_number')) {
                $table->string('account_number', 50)->nullable()->after('bank_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('customers', 'bank_name')) {
                $columns[] = 'bank_name';
            }
            if (Schema::hasColumn('customers', 'account_number')) {
                $columns[] = 'account_number';
            }
            if (count($columns) > 0) {
                $table->dropColumn($columns);
            }
        });
    }
};
