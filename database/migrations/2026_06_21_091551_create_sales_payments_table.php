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
        if (!Schema::hasTable('sales_payments')) {
            Schema::create('sales_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
                $table->string('payment_number', 30);
                $table->decimal('amount', 15, 2);
                $table->date('payment_date');
                $table->string('payment_method', 20); // Transfer, Cash, Cheque, etc.
                $table->string('reference', 100)->nullable(); // No. Giro/Cheque/Transfer
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->string('attachment')->nullable(); // File bukti bayar (receipt)
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['sales_invoice_id', 'payment_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};
