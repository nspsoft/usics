<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Finance\Coa;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceLedgerSyncTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $company;
    private $customer;
    private $supplier;
    private $warehouse;
    private $unit;
    private $product;
    private $salesOrder;
    private $purchaseOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'code' => 'COMP-TEST',
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '08123456789',
            'address' => 'Test Customer Address',
            'code' => 'CUST-001',
            'payment_terms' => 'COD',
            'payment_days' => 0,
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'phone' => '08129876543',
            'address' => 'Test Supplier Address',
            'code' => 'SUPP-001',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
            'type' => 'warehouse',
            'allow_negative_stock' => true,
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
            'sku' => 'TEST-PROD-SYNC',
            'unit_id' => $this->unit->id,
            'category_id' => $category->id,
            'product_type' => 'finished_good',
            'is_sold' => true,
            'selling_price' => 100000,
            'cost_price' => 50000,
        ]);

        $this->salesOrder = SalesOrder::create([
            'so_number' => 'SO-001',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => '2026-06-22',
            'status' => 'confirmed',
            'total' => 1000000,
            'created_by' => $this->user->id,
        ]);

        $this->purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => '2026-06-22',
            'status' => 'approved',
            'total' => 3000000,
            'created_by' => $this->user->id,
        ]);
    }

    private function createRequiredCoas(): void
    {
        Coa::create(['code' => '1120', 'name' => 'Accounts Receivable', 'type' => 'Asset']);
        Coa::create(['code' => '1130', 'name' => 'Inventory', 'type' => 'Asset']);
        Coa::create(['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'Liability']);
        Coa::create(['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'Revenue']);
        Coa::create(['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'Expense']);
    }

    public function test_sync_fails_when_required_coas_are_missing(): void
    {
        // No COAs created
        $response = $this->actingAs($this->user)
            ->post(route('finance.ledger.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'Missing required Chart of Accounts'));
    }

    public function test_sync_creates_ledger_journals_for_sales_and_purchase_invoices(): void
    {
        $this->createRequiredCoas();

        // 1. Create Sales Invoices (one valid, one draft, one cancelled)
        $sales1 = SalesInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-SALES-001',
            'customer_id' => $this->customer->id,
            'sales_order_id' => $this->salesOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => SalesInvoice::STATUS_SENT,
            'tax_amount_manual' => 0,
        ]);
        $sales1->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $salesDraft = SalesInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-SALES-DRAFT',
            'customer_id' => $this->customer->id,
            'sales_order_id' => $this->salesOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => SalesInvoice::STATUS_DRAFT,
            'tax_amount_manual' => 0,
        ]);
        $salesDraft->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $salesCancelled = SalesInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-SALES-CANCELLED',
            'customer_id' => $this->customer->id,
            'sales_order_id' => $this->salesOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => SalesInvoice::STATUS_CANCELLED,
            'tax_amount_manual' => 0,
        ]);
        $salesCancelled->items()->create([
            'product_id' => $this->product->id,
            'qty' => 20,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        // 2. Create Purchase Invoices (one valid, one cancelled)
        $purchase1 = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-PURCHASE-001',
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => PurchaseInvoice::STATUS_UNPAID,
            'total_amount' => 3000000,
        ]);

        $purchaseCancelled = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-PURCHASE-CANCELLED',
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => PurchaseInvoice::STATUS_CANCELLED,
            'total_amount' => 4000000,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('finance.ledger.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(
            'Successfully synced 1 sales invoices and 1 purchase invoices to the general ledger.',
            session('success')
        );

        // Verify Journal for Sales Invoice 1
        $salesJournal = Journal::where('reference', 'INV-SALES-001')->first();
        $this->assertNotNull($salesJournal);
        $this->assertEquals('Sales Invoice Sync - INV-SALES-001', $salesJournal->description);
        $this->assertEquals('2026-06-22', $salesJournal->date->toDateString());

        // Verify journal items:
        // Debit AR (1120) = 1,000,000
        // Credit Sales Revenue (4100) = 1,000,000
        // Debit COGS (5100) = 700,000
        // Credit Inventory (1130) = 700,000
        $items = $salesJournal->items()->get()->keyBy(function ($item) {
            return $item->coa->code;
        });

        $this->assertCount(4, $items);
        $this->assertEquals(1000000, $items['1120']->debit);
        $this->assertEquals(0, $items['1120']->credit);
        
        $this->assertEquals(0, $items['4100']->debit);
        $this->assertEquals(1000000, $items['4100']->credit);

        $this->assertEquals(700000, $items['5100']->debit);
        $this->assertEquals(0, $items['5100']->credit);

        $this->assertEquals(0, $items['1130']->debit);
        $this->assertEquals(700000, $items['1130']->credit);

        // Verify Journal for Purchase Invoice 1
        $purchaseJournal = Journal::where('reference', 'INV-PURCHASE-001')->first();
        $this->assertNotNull($purchaseJournal);
        $this->assertEquals('Purchase Invoice Sync - INV-PURCHASE-001', $purchaseJournal->description);

        // Verify journal items:
        // Debit Inventory (1130) = 3,000,000
        // Credit Accounts Payable (2110) = 3,000,000
        $pItems = $purchaseJournal->items()->get()->keyBy(function ($item) {
            return $item->coa->code;
        });

        $this->assertCount(2, $pItems);
        $this->assertEquals(3000000, $pItems['1130']->debit);
        $this->assertEquals(0, $pItems['1130']->credit);

        $this->assertEquals(0, $pItems['2110']->debit);
        $this->assertEquals(3000000, $pItems['2110']->credit);

        // Verify skipped draft and cancelled
        $this->assertNull(Journal::where('reference', 'INV-SALES-DRAFT')->first());
        $this->assertNull(Journal::where('reference', 'INV-SALES-CANCELLED')->first());
        $this->assertNull(Journal::where('reference', 'INV-PURCHASE-CANCELLED')->first());
    }

    public function test_sync_does_not_duplicate_existing_journals(): void
    {
        $this->createRequiredCoas();

        // Create Invoice
        $sales1 = SalesInvoice::create([
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-SALES-DUP-TEST',
            'customer_id' => $this->customer->id,
            'sales_order_id' => $this->salesOrder->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'status' => SalesInvoice::STATUS_SENT,
            'tax_amount_manual' => 0,
        ]);
        $sales1->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_id' => $this->unit->id,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        // Pre-create Journal for this reference (representing already synced)
        Journal::create([
            'reference' => 'INV-SALES-DUP-TEST',
            'date' => '2026-06-22',
            'description' => 'Existing Sales Invoice Journal',
            'status' => 'posted',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('finance.ledger.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('info');
        $this->assertEquals(
            'All invoices are already synchronized with the general ledger. No new entries created.',
            session('info')
        );

        // Ensure no new journal items were added to the existing journal
        $journal = Journal::where('reference', 'INV-SALES-DUP-TEST')->first();
        $this->assertEquals(0, $journal->items()->count());
    }

    public function test_ledger_index_filters_by_date(): void
    {
        $this->createRequiredCoas();

        // Create Journals on different dates
        Journal::create([
            'reference' => 'JRN-OLD',
            'date' => '2026-05-15',
            'description' => 'May transaction',
            'status' => 'posted',
        ]);

        Journal::create([
            'reference' => 'JRN-NEW',
            'date' => '2026-06-15',
            'description' => 'June transaction',
            'status' => 'posted',
        ]);

        // Request with date range matching only June transaction
        $response = $this->actingAs($this->user)
            ->get(route('finance.ledger', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30'
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Ledger')
            ->has('journals.data', 1)
            ->where('journals.data.0.reference', 'JRN-NEW')
        );
    }
}
