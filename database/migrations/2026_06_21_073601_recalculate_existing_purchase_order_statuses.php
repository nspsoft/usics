<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\PurchaseOrder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            PurchaseOrder::whereIn('status', [
                PurchaseOrder::STATUS_RECEIVED,
                PurchaseOrder::STATUS_PARTIAL
            ])->get()->each(function ($po) {
                $po->updateStatusBasedOnItems();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to recalculate PO statuses during migration: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
