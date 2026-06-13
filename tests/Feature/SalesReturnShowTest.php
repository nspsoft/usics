<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnShowTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $customer;
    protected $warehouse;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TC01'
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $unit = Unit::create(['name' => 'Piece', 'code' => 'PCS', 'type' => 'Unit']);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST01',
            'company_id' => $this->company->id
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main WH',
            'code' => 'MWH01',
            'company_id' => $this->company->id
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'PROD-001',
            'unit_id' => $unit->id,
            'type' => 'finished_goods',
            'company_id' => $this->company->id
        ]);
    }

    public function test_sales_return_show_resolves_model_binding()
    {
        // Create a sales return
        $salesReturn = SalesReturn::create([
            'number' => 'SRT/20260613/0001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'return_date' => now(),
            'status' => 'draft',
            'created_by' => $this->user->id,
            'total_amount' => 1000
        ]);

        // Add an item
        $salesReturn->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'unit_price' => 200,
            'total_price' => 1000
        ]);

        // GET /sales/returns/{return}
        $response = $this->get(route('sales.returns.show', $salesReturn->id));

        $response->assertStatus(200);
        
        $response->assertInertia(fn ($page) => $page
            ->component('Sales/Returns/Show')
            ->has('salesReturn')
        );

        $props = $response->original->getData()['page']['props'];
        $this->assertEquals($salesReturn->id, $props['salesReturn']['id']);
        $this->assertEquals('SRT/20260613/0001', $props['salesReturn']['number']);
        $this->assertCount(1, $props['salesReturn']['items']);
        $this->assertNotNull($props['salesReturn']['customer']);
        $this->assertNotNull($props['salesReturn']['warehouse']);
    }
}
