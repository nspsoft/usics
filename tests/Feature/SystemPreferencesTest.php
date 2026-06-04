<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Category;
use App\Models\ProductStock;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $company;
    private $customer;
    private $product;
    private $warehouse;
    private $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'code' => 'TEST-COMP',
            'name' => 'Test Company',
            'email' => 'test@comp.com',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'cust@test.com',
            'phone' => '0812345678',
            'address' => 'Jl. Test Address',
            'code' => 'CUST-TEST-PREF',
            'payment_terms' => 'COD',
            'payment_days' => 0,
        ]);

        $this->unit = Unit::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
        ]);

        $category = Category::create([
            'code' => 'TEST',
            'name' => 'Test Category',
            'company_id' => $this->company->id,
            'type' => 'product',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TEST-PROD-PREF',
            'unit_id' => $this->unit->id,
            'category_id' => $category->id,
            'product_type' => 'finished_good',
            'is_sold' => true,
            'selling_price' => 100000,
            'cost_price' => 50000,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-TEST-PREF',
            'name' => 'Test Warehouse',
            'type' => 'warehouse',
            'allow_negative_stock' => false, // Warehouse negative stock default to false
        ]);
    }

    public function test_preferences_page_is_accessible_to_authenticated_user()
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.preferences'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/SystemPreferences')
            ->has('preferences')
        );
    }

    public function test_preferences_can_be_updated()
    {
        $response = $this->actingAs($this->user)
            ->put(route('settings.preferences.update'), [
                'preferences' => [
                    'default_theme' => 'light',
                    'sidebar_collapsed' => true,
                    'items_per_page' => 50,
                    'auto_update_stock' => false,
                    'allow_negative_stock' => true,
                    'require_po_number' => true,
                    'default_payment_terms' => 'NET 14',
                    'auto_so_from_quotation' => true,
                    'email_on_new_order' => false,
                    'notify_low_stock' => false,
                    'session_timeout' => 60,
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Preferences saved successfully.');

        $this->assertEquals('light', AppSetting::get('default_theme'));
        $this->assertEquals(true, AppSetting::get('sidebar_collapsed'));
        $this->assertEquals(50, AppSetting::get('items_per_page'));
        $this->assertEquals(false, AppSetting::get('auto_update_stock'));
        $this->assertEquals(true, AppSetting::get('allow_negative_stock'));
        $this->assertEquals(true, AppSetting::get('require_po_number'));
        $this->assertEquals('NET 14', AppSetting::get('default_payment_terms'));
        $this->assertEquals(true, AppSetting::get('auto_so_from_quotation'));
        $this->assertEquals(false, AppSetting::get('email_on_new_order'));
        $this->assertEquals(false, AppSetting::get('notify_low_stock'));
        $this->assertEquals(60, AppSetting::get('session_timeout'));
    }

    public function test_require_po_number_preference()
    {
        // 1. Enable require_po_number system preference
        AppSetting::set('require_po_number', true);

        $soData = [
            'so_number' => 'SO-TEST-PO-REQ',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 5,
                    'unit_id' => $this->unit->id,
                    'unit_price' => 100000,
                ]
            ]
        ];

        // Store should fail without customer_po_number
        $response = $this->actingAs($this->user)
            ->post(route('sales.orders.store'), $soData);
        $response->assertSessionHasErrors(['customer_po_number']);

        // Store should succeed when customer_po_number is provided
        $soData['customer_po_number'] = 'PO-12345';
        $response = $this->actingAs($this->user)
            ->post(route('sales.orders.store'), $soData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sales.orders.index'));

        // 2. Disable require_po_number system preference
        AppSetting::set('require_po_number', false);
        $soData['so_number'] = 'SO-TEST-PO-NO-REQ';
        unset($soData['customer_po_number']);

        // Store should succeed even without customer_po_number
        $response = $this->actingAs($this->user)
            ->post(route('sales.orders.store'), $soData);
        $response->assertSessionHasNoErrors();
    }

    public function test_auto_so_from_quotation_preference()
    {
        // 1. Disable auto_so_from_quotation system preference
        AppSetting::set('auto_so_from_quotation', false);

        $quotation = Quotation::create([
            'number' => 'Q-TEST-1',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $quotation->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'unit_price' => 100000,
            'total_price' => 500000,
        ]);
        $quotation->calculateTotal();

        // Accept quotation should NOT auto convert to SO
        $response = $this->actingAs($this->user)
            ->post(route('sales.quotations.accept', $quotation->id));
        $response->assertSessionHasNoErrors();
        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);
        $this->assertEquals(0, SalesOrder::where('notes', 'like', "%{$quotation->number}%")->count());

        // 2. Enable auto_so_from_quotation system preference
        AppSetting::set('auto_so_from_quotation', true);
        
        $quotation2 = Quotation::create([
            'number' => 'Q-TEST-2',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $quotation2->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'unit_price' => 100000,
            'total_price' => 500000,
        ]);
        $quotation2->calculateTotal();

        // Accept quotation should AUTO convert to SO
        $response = $this->actingAs($this->user)
            ->post(route('sales.quotations.accept', $quotation2->id));
        $response->assertSessionHasNoErrors();
        $quotation2->refresh();
        $this->assertEquals('converted', $quotation2->status);
        $this->assertEquals(1, SalesOrder::where('notes', 'like', "%{$quotation2->number}%")->count());
    }

    public function test_allow_negative_stock_preference()
    {
        // Setup initial stock = 5
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand' => 5,
            'qty_reserved' => 0,
            'qty_incoming' => 0,
            'qty_outgoing' => 0,
            'avg_cost' => 50000,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-NEG',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'status' => 'confirmed',
            'total' => 1000000,
            'created_by' => $this->user->id,
        ]);
        $soItem = $so->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);

        $do = DeliveryOrder::create([
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'do_number' => 'DO-TEST-NEG',
            'delivery_date' => now(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $do->items()->create([
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'qty_delivered' => 10, // Exceeds available stock of 5
            'unit_id' => $this->unit->id,
        ]);

        // 1. Allow negative stock is FALSE (both system and warehouse)
        AppSetting::set('allow_negative_stock', false);
        $this->warehouse->update(['allow_negative_stock' => false]);

        // Attempting to ship/complete DO should throw an error session alert
        $response = $this->actingAs($this->user)
            ->post(route('sales.deliveries.complete', $do->id));
        $response->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'Stok tidak mencukupi'));
        $do->refresh();
        $this->assertEquals('draft', $do->status); // Status remains draft

        // 2. Allow negative stock is TRUE system-wide
        AppSetting::set('allow_negative_stock', true);
        $response = $this->actingAs($this->user)
            ->post(route('sales.deliveries.complete', $do->id));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $do->refresh();
        $this->assertEquals('completed', $do->status);

        // Verify stock is now negative (-5)
        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(-5.0, $stock->qty_on_hand);
    }

    public function test_auto_update_stock_preference()
    {
        // Setup initial stock = 20
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand' => 20,
            'qty_reserved' => 0,
            'qty_incoming' => 0,
            'qty_outgoing' => 0,
            'avg_cost' => 50000,
        ]);

        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-AUTO',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'status' => 'confirmed',
            'total' => 1000000,
            'created_by' => $this->user->id,
        ]);
        $soItem = $so->items()->create([
            'product_id' => $this->product->id,
            'qty' => 20,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'subtotal' => 2000000,
        ]);

        // 1. auto_update_stock is FALSE: stock should NOT deduct when status changes to 'shipped'
        AppSetting::set('auto_update_stock', false);

        $do1 = DeliveryOrder::create([
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'do_number' => 'DO-TEST-AUTO-1',
            'delivery_date' => now(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $do1->items()->create([
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 10,
            'qty_delivered' => 10,
            'unit_id' => $this->unit->id,
        ]);

        // Update status to shipped
        $response = $this->actingAs($this->user)
            ->patch(route('sales.deliveries.update-status', $do1->id), ['status' => 'shipped']);
        $response->assertSessionHasNoErrors();
        $do1->refresh();
        $this->assertEquals('shipped', $do1->status);

        // Verify stock is still 20 (not deducted)
        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(20.0, $stock->qty_on_hand);

        // 2. auto_update_stock is TRUE: stock SHOULD deduct when status changes to 'shipped'
        AppSetting::set('auto_update_stock', true);
        
        $do2 = DeliveryOrder::create([
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'do_number' => 'DO-TEST-AUTO-2',
            'delivery_date' => now(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $do2->items()->create([
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
            'unit_id' => $this->unit->id,
        ]);

        // Update status to shipped
        $response = $this->actingAs($this->user)
            ->patch(route('sales.deliveries.update-status', $do2->id), ['status' => 'shipped']);
        $response->assertSessionHasNoErrors();
        $do2->refresh();
        $this->assertEquals('shipped', $do2->status);

        // Verify stock is now 15 (deducted by 5)
        $stock->refresh();
        $this->assertEquals(15.0, $stock->qty_on_hand);
    }

    public function test_notify_low_stock_preference()
    {
        // 1. Disable notify_low_stock system preference
        AppSetting::set('notify_low_stock', false);

        $exitCode = Artisan::call('inventory:check-low-stock');
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(str_contains(Artisan::output(), 'disabled'));
    }
}
