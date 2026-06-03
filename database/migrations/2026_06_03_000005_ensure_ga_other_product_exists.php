<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasTable('products')) {
            return;
        }

        $sku = 'GA-OTHER';

        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            $unitId = null;

            if (Schema::hasTable('units')) {
                $unitId = DB::table('units')
                    ->where('company_id', $companyId)
                    ->whereIn('code', ['PCS', 'UNIT'])
                    ->orderByRaw("FIELD(code,'PCS','UNIT')")
                    ->value('id');

                if (!$unitId) {
                    $unitId = DB::table('units')->where('company_id', $companyId)->value('id');
                }
            }

            $existing = DB::table('products')
                ->where('company_id', $companyId)
                ->where('sku', $sku)
                ->first();

            $payload = [
                'name' => 'GA Other',
                'description' => 'General Affair - item jasa / kebutuhan lain-lain (gunakan notes untuk detail).',
                'type' => 'service',
                'product_type' => 'spare_part',
                'unit_id' => $unitId,
                'purchase_unit_id' => $unitId,
                'sales_unit_id' => $unitId,
                'cost_price' => 0,
                'selling_price' => 0,
                'min_stock' => 0,
                'max_stock' => 0,
                'reorder_point' => 0,
                'reorder_qty' => 0,
                'lead_time_days' => 0,
                'is_manufactured' => false,
                'is_purchased' => true,
                'is_sold' => false,
                'track_serial' => false,
                'track_batch' => false,
                'track_expiry' => false,
                'is_active' => true,
                'updated_at' => now(),
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('products')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('products')->insert([
                'company_id' => $companyId,
                'sku' => $sku,
                ...$payload,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        DB::table('products')
            ->where('sku', 'GA-OTHER')
            ->update([
                'is_active' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }
};

