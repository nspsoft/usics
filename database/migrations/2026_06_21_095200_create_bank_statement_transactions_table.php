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
        Schema::create('bank_statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('type', 2); // CR (Credit/In) or DB (Debit/Out)
            $table->string('reference_number', 100)->nullable();
            $table->string('bank_name', 50); // BCA, Mandiri, Generic
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('sales_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hash', 64)->unique(); // SHA-256 hash to prevent duplicates
            $table->timestamps();

            $table->index(['transaction_date', 'type']);
            $table->index('reconciled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_transactions');
    }
};
