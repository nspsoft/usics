<?php

namespace Tests\Unit;

use App\Imports\CustomerContactImport;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CustomerContactImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_upserts_contacts_by_email_or_phone(): void
    {
        Customer::query()->create([
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

        $import = new CustomerContactImport(true);
        $import->collection(new Collection([
            new Collection([
                'customer_code' => 'CUST-1',
                'pic_name' => 'Henry',
                'position' => 'Purchasing',
                'phone' => '0817-007-1405',
                'email' => 'HENRY@EXAMPLE.COM',
            ]),
            new Collection([
                'customer_code' => 'CUST-1',
                'pic_name' => 'Henry',
                'position' => 'Manager',
                'phone' => '08170071405',
                'email' => 'henry@example.com',
            ]),
        ]));

        $this->assertSame(1, CustomerContact::query()->count());
        $this->assertDatabaseHas('customer_contacts', [
            'email' => 'henry@example.com',
            'phone' => '08170071405',
            'position' => 'Manager',
        ]);
    }
}

