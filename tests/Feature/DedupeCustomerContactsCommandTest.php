<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DedupeCustomerContactsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_duplicates_by_email(): void
    {
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
            'name' => 'Henry',
            'email' => 'henry@example.com',
            'phone' => null,
            'position' => null,
        ]);

        CustomerContact::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Henry Handayani',
            'email' => 'HENRY@EXAMPLE.COM',
            'phone' => '08170071405',
            'position' => 'Purchasing',
        ]);

        $this->assertDatabaseCount('customer_contacts', 2);

        Artisan::call('app:dedupe-customer-contacts', ['--customer-id' => (string) $customer->id]);

        $this->assertDatabaseCount('customer_contacts', 1);
        $this->assertDatabaseHas('customer_contacts', [
            'customer_id' => $customer->id,
            'email' => 'henry@example.com',
        ]);
    }
}

