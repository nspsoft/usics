<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleHardResetSalesContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_hard_reset_sales_deletes_additional_contacts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
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
        ]);

        CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'name' => 'PIC A',
            'email' => 'pic@example.com',
            'phone' => '08170071405',
            'position' => 'Purchasing',
        ]);

        $this->assertDatabaseCount('customer_contacts', 1);

        $response = $this->post(route('settings.database.module-reset'), [
            'module' => 'sales',
            'mode' => 'hard',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseCount('customer_contacts', 0);
    }
}
