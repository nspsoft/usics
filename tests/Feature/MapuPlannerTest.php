<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\SalesForecast;
use App\Models\PurchaseRequest;
use App\Models\Department;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapuPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $rawMaterial;
    protected Product $finishedGood;
    protected Department $department;
    protected Customer $customer;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a user
        $this->user = User::factory()->create();

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST-TEST',
        ]);

        // Create Warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH01',
        ]);

        // 2. Create raw material
        $this->rawMaterial = Product::create([
            'sku' => 'COIL-TEST-1.0',
            'name' => 'Test Raw Material Coil 1.0mm',
            'product_type' => 'raw_material',
            'type' => 'product',
            'is_purchased' => true,
            'is_manufactured' => false,
            'min_stock' => 100.0,
            'is_active' => true,
        ]);

        // 3. Create finished good
        $this->finishedGood = Product::create([
            'sku' => 'FG-SHEET-TEST',
            'name' => 'Test Finished Good Sheet',
            'product_type' => 'finished_good',
            'type' => 'product',
            'is_purchased' => false,
            'is_manufactured' => true,
            'is_active' => true,
        ]);

        // 4. Create BOM
        $bom = Bom::create([
            'code' => 'BOM-FG-TEST',
            'name' => 'Test BOM',
            'product_id' => $this->finishedGood->id,
            'qty' => 1.0,
            'status' => Bom::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        // Component in BOM (uses 2.0 units of Raw Material with 5% scrap)
        BomComponent::create([
            'bom_id' => $bom->id,
            'product_id' => $this->rawMaterial->id,
            'qty' => 2.0,
            'scrap_rate' => 5.0, // 5% scrap
        ]);

        // 5. Create department
        $this->department = Department::create([
            'name' => 'PPIC',
            'code' => 'PPIC',
            'is_active' => true,
        ]);
    }

    public function test_mapu_page_is_accessible_to_authenticated_users()
    {
        $response = $this->actingAs($this->user)->get('/purchasing/mapu');
        $response->assertStatus(200);
    }

    public function test_mapu_calculation_logic()
    {
        // Set sales forecast for target month (e.g. 2026-09)
        // Kuantitas forecast = 50.
        // Kebutuhan raw material per FG = 2.0 * (1 + 0.05) = 2.10.
        // Gross demand = 50 * 2.10 = 105.0.
        SalesForecast::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->finishedGood->id,
            'period' => '2026-09-01',
            'qty_forecast' => 50.0,
        ]);

        // Current stock = 30.
        ProductStock::create([
            'product_id' => $this->rawMaterial->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand' => 30.0,
        ]);

        // Run calculation: planning month = 2026-06, arrival month = 2026-09
        $response = $this->actingAs($this->user)->json('GET', '/purchasing/mapu/calculate', [
            'planning_month' => '2026-06',
            'arrival_month' => '2026-09',
        ]);

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(1, $data['results']);
        
        $rmResult = $data['results'][0];
        $this->assertEquals($this->rawMaterial->sku, $rmResult['sku']);
        $this->assertEquals(105.0, $rmResult['gross_demand']); // 50 * 2.10
        $this->assertEquals(30.0, $rmResult['current_stock']);
        $this->assertEquals(100.0, $rmResult['safety_stock']); // min_stock = 100.0
        
        // Projected Ending Stock = Stock (30) + Outstanding PO (0) - Wait period consumption (0) = 30.
        $this->assertEquals(30.0, $rmResult['projected_ending_stock']);
        
        // Net Requirement = Gross Demand (105) + Safety Stock (100) - Projected Ending Stock (30) = 175.
        $this->assertEquals(175.0, $rmResult['net_requirement']);
        $this->assertEquals('ORDER', $rmResult['recommendation']);
    }

    public function test_create_purchase_request_from_mapu()
    {
        $response = $this->actingAs($this->user)->postJson('/purchasing/mapu/create-pr', [
            'request_date' => '2026-06-15',
            'department' => $this->department->name,
            'requester' => $this->user->name,
            'notes' => 'MAPU Test Request',
            'items' => [
                [
                    'product_id' => $this->rawMaterial->id,
                    'qty' => 175.0,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify database entry
        $this->assertDatabaseHas('purchase_requests', [
            'department' => $this->department->name,
            'requester' => $this->user->name,
            'notes' => 'MAPU Test Request',
        ]);

        $pr = PurchaseRequest::where('requester', $this->user->name)->first();
        $this->assertCount(1, $pr->items);
        $this->assertEquals($this->rawMaterial->id, $pr->items[0]->product_id);
        $this->assertEquals(175.0, $pr->items[0]->qty);
    }
}
