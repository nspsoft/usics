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
        Schema::create('mtc_documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 20)->default('pdf'); // pdf, image
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('date_of_issue')->nullable();
            $table->string('order_no')->nullable();
            $table->string('po_no')->nullable();
            $table->string('commodity')->nullable();
            $table->string('spec_and_type')->nullable();
            $table->string('customer')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->enum('status', ['draft', 'verified', 'pushed_to_sap', 'rejected'])->default('draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index('supplier_name');
            $table->index('certificate_number');
        });

        Schema::create('mtc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mtc_document_id')->constrained('mtc_documents')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_no')->nullable();
            $table->string('heat_no')->nullable();
            $table->string('size')->nullable(); // e.g. 6.02x1045xC
            $table->integer('quantity')->nullable();
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->string('position')->nullable(); // T (Top), M (Middle), B (Bottom)

            // Tensile Test
            $table->decimal('yp_mpa', 8, 2)->nullable(); // Yield Point
            $table->decimal('ts_mpa', 8, 2)->nullable(); // Tensile Strength
            $table->decimal('el_percent', 5, 2)->nullable(); // Elongation
            $table->decimal('yr_percent', 5, 2)->nullable(); // Yield Ratio

            // Bend Test
            $table->string('bend_test', 50)->nullable(); // e.g. "Good"

            // Impact Test (multiple readings possible)
            $table->json('impact_test_data')->nullable(); // [{energy, sf, temp, position}]

            // Chemical Composition — Ladle Analysis
            $table->json('chemical_ladle')->nullable(); // {C, Si, Mn, P, S, Cr, Ni, B, Cu, Mo, Nb, Ti, V, CEQ}

            // Chemical Composition — Product Analysis
            $table->json('chemical_product')->nullable(); // {C, Si, Mn, P, S, Cr, Ni, B, Cu, Mo, Nb, Ti, V, CEQ}

            // Division (L = Ladle, P = Product)
            $table->string('division', 10)->nullable();

            // Compliance Auto-Check
            $table->enum('compliance_status', ['pass', 'fail', 'warning', 'unchecked'])->default('unchecked');
            $table->text('compliance_notes')->nullable();

            $table->timestamps();

            $table->index('heat_no');
            $table->index('product_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtc_items');
        Schema::dropIfExists('mtc_documents');
    }
};
