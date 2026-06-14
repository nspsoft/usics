<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_sales_cycle()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::first();
        if (!$company) {
            $company = Company::create([
                'code' => 'TEST-001',
                'name' => 'Test Company',
                'email' => 'test@company.com',
            ]);
        }

        $customer = Customer::first();
        if (!$customer) {
            $customer = Customer::create([
               'company_id' => $company->id,
               'name' => 'Test Customer Feature',
               'email' => 'customer.feature@test.com',
               'phone' => '0812999999',
               'address' => 'Jl. Feature Test',
               'code' => 'CUST-TEST',
            ]);
        }

        $product = Product::first();
        if (!$product) {
            $unit = Unit::firstOrCreate(['code' => 'PCS', 'name' => 'Pieces', 'company_id' => $company->id]);
            $category = \App\Models\Category::firstOrCreate(['code' => 'TEST', 'name' => 'Test Category', 'company_id' => $company->id, 'type' => 'product']);
            
            $product = Product::create([
                'company_id' => $company->id,
                'name' => 'Test Product',
                'sku' => 'TEST-PROD-001',
                'unit_id' => $unit->id,
                'category_id' => $category->id,
                'product_type' => 'finished_good',
                'is_sold' => true,
                'selling_price' => 100000,
                'cost_price' => 50000,
            ]);
        }

        $warehouse = Warehouse::first();
        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'code' => 'WH-TEST',
                'name' => 'Test Warehouse',
                'type' => 'warehouse',
            ]);
        }

        // Create initial stock
        \App\Models\ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 1000,
            'qty_reserved' => 0,
            'qty_incoming' => 0,
            'qty_outgoing' => 0,
            'avg_cost' => 50000,
        ]);

        // 2. Create Quotation
        $quotationData = [
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'unit_price' => 100000,
                ]
            ]
        ];

        $initialCount = \App\Models\Quotation::count();

        $response = $this->post(route('sales.quotations.store'), $quotationData);
        
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sales.quotations.index')); 

        $newCount = \App\Models\Quotation::count();
        $this->assertEquals($initialCount + 1, $newCount, "Quotation count did not increase. Store failed.");

        $quotation = \App\Models\Quotation::latest('id')->first();
        $this->assertEquals('draft', $quotation->status, 'Quotation status should be draft');

        // 3. Accept Quotation
        // Actually, route is sales.quotations.accept (POST)
        // Wait, index method returns Inertia.
        // Let's use the route name.
        $this->post(route('sales.quotations.accept', $quotation->id));
        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status, 'Quotation status should be accepted');

        // 4. Convert to SO
        $this->post(route('sales.quotations.convert', $quotation->id));
        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status, 'Quotation status should be converted');

        $so = \App\Models\SalesOrder::latest('id')->first();
        $this->assertEquals('draft', $so->status, 'Sales Order status should be draft');

        // 5. Confirm SO
        $so->customer_po_number = 'PO-TEST-12345';
        $so->save();

        $this->post(route('sales.orders.confirm', $so->id));
        $so->refresh();
        $this->assertEquals('confirmed', $so->status, 'Sales Order status should be confirmed');

        // 6. Create DO (Delivery)
        $doData = [
            'sales_order_id' => $so->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => now()->toDateString(),
            'vehicle_id' => 'manual',
            'vehicle_number' => 'B 1234 TEST',
            'driver_name' => 'Test Driver',
            'items' => [
                [
                    'sales_order_item_id' => $so->items->first()->id,
                    'qty_delivered' => 10, // Full delivery
                ]
            ]
        ];

        $response = $this->post(route('sales.deliveries.store'), $doData);
        $response->assertSessionHasNoErrors();
        
        $do = \App\Models\DeliveryOrder::latest('id')->first();
        $this->assertEquals('draft', $do->status, 'Delivery Order status should be draft');

        // 7. Complete DO
        // Route: PATCH /sales/deliveries/{id}/status
        // Or POST /sales/deliveries/{id}/complete if available.
        // DeliveryOrderController has `complete` method at line 335.
        // Route is `orders.complete`? No.
        // Route line 204: Route::post('/deliveries/{delivery_order}/complete', ...)->name('deliveries.complete');
        // Let's use that first as it is cleaner.
        $this->post(route('sales.deliveries.complete', $do->id));
        $do->refresh();
        $this->assertEquals('completed', $do->status, 'Delivery Order status should be completed');

        // 8. Create Invoice
        // Route: sales.deliveries.create-invoice (POST)
        $this->post(route('sales.deliveries.create-invoice', $do->id));
        
        $invoice = \App\Models\SalesInvoice::latest('id')->first();
        $this->assertEquals('draft', $invoice->status, 'Invoice status should be draft');

        // 9. Confirm Invoice
        // Route: sales.invoices.confirm (POST)
        $this->post(route('sales.invoices.confirm', $invoice->id));
        $invoice->refresh();
        $this->assertEquals('issued', $invoice->status, 'Invoice status should be issued');

        // 10. Pay Invoice
        // Route: sales.invoices.pay (POST)
        $paymentData = [
            'amount' => $invoice->total,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Bank Transfer',
        ];
        $this->post(route('sales.invoices.pay', $invoice->id), $paymentData);
        
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status, 'Invoice status should be paid');
    }
}
