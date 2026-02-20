<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('emeterai_serial')->nullable()->after('notes');
            $table->timestamp('emeterai_stamped_at')->nullable()->after('emeterai_serial');
            $table->string('emeterai_pdf_path')->nullable()->after('emeterai_stamped_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['emeterai_serial', 'emeterai_stamped_at', 'emeterai_pdf_path']);
        });
    }
};
