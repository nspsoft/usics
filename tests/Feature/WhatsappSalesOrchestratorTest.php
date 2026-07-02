<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SalesOrder;
use App\Models\ProductStock;
use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\WorkOrder;
use App\Models\PurchaseRequest;
use App\Services\WhatsappBotService;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhatsappSalesOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_sales_orchestrator_flow()
    {
        // 1. Setup Base Data
        $company = Company::create([
            'code' => 'USC',
            'name' => 'PT United Steel Center Indonesia',
            'email' => 'info@usc-indonesia.co.id',
        ]);

        $user = User::factory()->create();

        $department = \App\Models\Department::create([
            'company_id' => $company->id,
            'code' => 'SALES',
            'name' => 'Sales & Marketing',
        ]);

        $position = \App\Models\Position::create([
            'company_id' => $company->id,
            'code' => 'SALES-REP',
            'name' => 'Sales Representative',
            'department_id' => $department->id,
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'nik' => 'EMP001',
            'full_name' => 'Sales Representative 1',
            'email' => 'sales1@usc-indonesia.co.id',
            'phone' => '08123456789',
            'status' => 'active',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'joining_date' => now(),
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Toyota Motor Manufacturing',
            'email' => 'purchasing@toyota.co.id',
            'phone' => '021-999999',
            'address' => 'Karawang',
            'code' => 'CUST-TOYOTA',
        ]);

        $unit = Unit::create([
            'company_id' => $company->id,
            'code' => 'PCS',
            'name' => 'Pieces',
        ]);

        // Create FG Product
        $fgProduct = Product::create([
            'company_id' => $company->id,
            'name' => 'Steel Sheet Disc Brake Blank',
            'sku' => 'FG-SHEET-001',
            'unit_id' => $unit->id,
            'product_type' => 'finished_good',
            'is_sold' => true,
            'selling_price' => 150000,
            'cost_price' => 100000,
            'is_active' => true,
        ]);

        // Create RM Product
        $rmProduct = Product::create([
            'company_id' => $company->id,
            'name' => 'Mother Coil HRC',
            'sku' => 'RM-HRC-001',
            'unit_id' => $unit->id,
            'product_type' => 'raw_material',
            'is_purchased' => true,
            'is_active' => true,
        ]);

        // Setup BOM for FG Product using RM Product
        $bom = Bom::create([
            'company_id' => $company->id,
            'code' => 'BOM-FG-001',
            'name' => 'BOM Steel Sheet Disc Brake Blank',
            'product_id' => $fgProduct->id,
            'qty' => 1,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        BomComponent::create([
            'bom_id' => $bom->id,
            'product_id' => $rmProduct->id,
            'qty' => 1.2, // 1 FG requires 1.2 RM
            'unit_id' => $unit->id,
            'scrap_rate' => 0,
            'sequence' => 1,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        // Setup Stocks
        ProductStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $fgProduct->id,
            'qty_on_hand' => 50,
            'qty_reserved' => 10, // 40 free stock
        ]);

        ProductStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $rmProduct->id,
            'qty_on_hand' => 10, // only 10 raw materials available
            'qty_reserved' => 0,
        ]);

        // Mock FonnteService to prevent real API calls
        $mockFonnte = $this->createMock(FonnteService::class);
        $mockFonnte->method('sendMessage')->willReturn(['success' => true]);
        $this->app->instance(FonnteService::class, $mockFonnte);

        // 2. Simulate Creating Draft SO (e.g. from WA PO Extractor)
        // Set up a Draft Sales Order
        $so = SalesOrder::create([
            'company_id' => $company->id,
            'so_number' => SalesOrder::generateSoNumber(),
            'customer_po_number' => 'PO-TOYOTA-123',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now(),
            'status' => 'draft',
            'discount_percent' => 0,
            'tax_percent' => 11,
            'created_by' => $user->id,
        ]);

        $so->items()->create([
            'product_id' => $fgProduct->id,
            'qty' => 100, // Deficit: 100 required - 40 free stock = 60 deficit
            'unit_price' => 150000,
        ]);

        $so->refresh();
        $so->calculateTotals();

        // Cache the draft SO ID against the employee's phone
        Cache::put("whatsapp_draft_so_08123456789", $so->id, 120);

        // 3. Trigger WhatsApp Bot Service with 'CONFIRM' message from employee
        $botService = $this->app->make(WhatsappBotService::class);
        $response = $botService->handleIncomingMessage('08123456789', 'CONFIRM');

        // 4. Assertions
        // Check Sales Order status transitioned to confirmed
        $so->refresh();
        $this->assertEquals('confirmed', $so->status);

        // Check WorkOrder created for deficit (60 Pcs)
        $workOrder = WorkOrder::where('sales_order_id', $so->id)->first();
        $this->assertNotNull($workOrder);
        $this->assertEquals(60, $workOrder->qty_planned);
        $this->assertEquals('draft', $workOrder->status);

        // Check PurchaseRequest created for RM deficit
        // Deficit: 60 FG * 1.2 RM = 72 RM required. Free stock is 10. RM Deficit = 62.
        $pr = PurchaseRequest::where('notes', 'like', "%SO #{$so->so_number}%")->first();
        $this->assertNotNull($pr);
        $this->assertEquals('draft', $pr->status);

        $prItem = $pr->items()->first();
        $this->assertNotNull($prItem);
        $this->assertEquals($rmProduct->id, $prItem->product_id);
        $this->assertEquals(62, $prItem->qty);

        // Verify response contains audit information
        $this->assertStringContainsString('Supply Chain Audit', $response);
        $this->assertStringContainsString('Defisit: 60 Pcs', $response);
        $this->assertStringContainsString('Draf WO Produksi Dibuat', $response);
        $this->assertStringContainsString('Draf PR Bahan Baku Dibuat', $response);
    }
}
