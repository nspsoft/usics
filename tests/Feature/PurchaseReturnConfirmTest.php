<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnConfirmTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $supplier;
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

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'code' => 'SUP01',
            'company_id' => $this->company->id
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main WH',
            'code' => 'MWH01',
            'company_id' => $this->company->id
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Raw Material',
            'sku' => 'RM-001',
            'unit_id' => $unit->id,
            'type' => 'raw_material',
            'company_id' => $this->company->id
        ]);
    }

    public function test_purchase_return_confirm_resolves_model_binding()
    {
        // Create a purchase return
        $purchaseReturn = PurchaseReturn::create([
            'number' => 'PRT/20260613/0001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'return_date' => now(),
            'status' => 'draft',
            'created_by' => $this->user->id
        ]);

        // Add a return item
        $purchaseReturn->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_price' => 100,
            'total_price' => 1000
        ]);

        // POST /purchasing/returns/{return}/confirm
        $response = $this->post(route('purchasing.purchase-returns.confirm', $purchaseReturn->id));

        // Let's assert redirect or success session
        $response->assertSessionHas('success');
        $this->assertEquals('confirmed', $purchaseReturn->fresh()->status);
    }
}
