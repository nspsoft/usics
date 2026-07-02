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
        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->foreignId('mtc_document_id')
                  ->after('mill_id')
                  ->nullable()
                  ->constrained('mtc_documents')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->dropForeign(['mtc_document_id']);
            $table->dropColumn('mtc_document_id');
        });
    }
};
