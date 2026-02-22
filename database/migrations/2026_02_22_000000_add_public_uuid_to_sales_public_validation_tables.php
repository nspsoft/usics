<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'public_uuid')) {
                $table->uuid('public_uuid')->nullable()->unique();
            }
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'public_uuid')) {
                $table->uuid('public_uuid')->nullable()->unique();
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'public_uuid')) {
                $table->uuid('public_uuid')->nullable()->unique();
            }
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_returns', 'public_uuid')) {
                $table->uuid('public_uuid')->nullable()->unique();
            }
        });

        $this->backfill('quotations');
        $this->backfill('delivery_orders');
        $this->backfill('sales_invoices');
        $this->backfill('sales_returns');
    }

    private function backfill(string $table): void
    {
        DB::table($table)
            ->select(['id'])
            ->whereNull('public_uuid')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'public_uuid' => (string) Str::uuid(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'public_uuid')) {
                $table->dropUnique('quotations_public_uuid_unique');
                $table->dropColumn('public_uuid');
            }
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'public_uuid')) {
                $table->dropUnique('delivery_orders_public_uuid_unique');
                $table->dropColumn('public_uuid');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'public_uuid')) {
                $table->dropUnique('sales_invoices_public_uuid_unique');
                $table->dropColumn('public_uuid');
            }
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sales_returns', 'public_uuid')) {
                $table->dropUnique('sales_returns_public_uuid_unique');
                $table->dropColumn('public_uuid');
            }
        });
    }
};
