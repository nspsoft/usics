<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicValidationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_validation_redirects_id_to_uuid(): void
    {
        $user = User::factory()->create();

        $warehouse = Warehouse::create([
            'company_id' => null,
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => null,
            'code' => 'CUST-TEST',
            'name' => 'Test Customer',
            'payment_terms' => 'NET30',
            'payment_days' => 30,
            'credit_limit' => 0,
            'currency' => 'IDR',
            'customer_type' => 'regular',
            'is_active' => true,
        ]);

        $salesOrder = SalesOrder::create([
            'company_id' => null,
            'so_number' => 'SO-TEST-001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 0,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 11,
            'tax_amount' => 0,
            'total' => 0,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::create([
            'number' => '001/QUOT/JRI-TEST/I/26',
            'public_uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'created_by' => $user->id,
        ]);

        $deliveryOrder = DeliveryOrder::create([
            'company_id' => null,
            'do_number' => '001/DO/JRI-TEST/I/26',
            'public_uuid' => (string) Str::uuid(),
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $invoice = SalesInvoice::create([
            'company_id' => null,
            'invoice_number' => '0001/INV/JRI-TEST/I/26',
            'public_uuid' => (string) Str::uuid(),
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'created_by' => $user->id,
        ]);

        $salesReturn = SalesReturn::create([
            'number' => 'RET-TEST-001',
            'public_uuid' => (string) Str::uuid(),
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'return_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => 0,
            'created_by' => $user->id,
        ]);

        $this->get(route('sales.quotations.public-validate', $quotation->id))
            ->assertRedirect(route('sales.quotations.public-validate', $quotation->public_uuid));

        $this->get(route('sales.deliveries.public-validate', $deliveryOrder->id))
            ->assertRedirect(route('sales.deliveries.public-validate', $deliveryOrder->public_uuid));

        $this->get(route('sales.invoices.public-validate', $invoice->id))
            ->assertRedirect(route('sales.invoices.public-validate', $invoice->public_uuid));

        $this->get(route('sales.returns.public-validate', $salesReturn->id))
            ->assertRedirect(route('sales.returns.public-validate', $salesReturn->public_uuid));
    }
}

