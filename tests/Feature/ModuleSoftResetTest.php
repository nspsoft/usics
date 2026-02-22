<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModuleSoftResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_soft_reset_keeps_master_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = now();

        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => null,
            'code' => 'WH-1',
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
            'is_default' => 1,
            'allow_negative_stock' => 0,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'company_id' => null,
            'code' => 'CUST-1',
            'name' => 'Customer A',
            'country' => 'ID',
            'payment_terms' => 'NET30',
            'payment_days' => 30,
            'credit_limit' => 0,
            'currency' => 'IDR',
            'customer_type' => 'regular',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('sales_orders')->insert([
            'company_id' => null,
            'so_number' => 'SO-001',
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'order_date' => $now->toDateString(),
            'status' => 'draft',
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 0,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 11,
            'tax_amount' => 0,
            'total' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('sales_orders', 1);

        $response = $this->post(route('settings.database.module-reset'), [
            'module' => 'sales',
            'mode' => 'soft',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('sales_orders', 0);
    }
}

