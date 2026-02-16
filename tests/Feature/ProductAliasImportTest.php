<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProductAliasImportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'CO001'
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function import_product_aliases_successfully()
    {
        // 1. Setup Data
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'type' => 'product',
            'product_type' => 'finished_good',
            'company_id' => $this->company->id,
            'cost_price' => 10000,
            'selling_price' => 20000,
            'is_active' => true
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST001',
            'company_id' => $this->company->id,
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'address' => 'Test Address'
        ]);

        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'code' => 'SUPP001',
            'company_id' => $this->company->id,
            'email' => 'supplier@test.com',
            'phone' => '0987654321',
            'address' => 'Supplier Address'
        ]);

        // 2. Mock Excel File
        $rows = [
            ['product_sku', 'partner_name', 'partner_type', 'alias_sku', 'alias_name'],
            ['SKU-001', 'Test Customer', 'customer', 'CUST-SKU-001', 'Customer Product Name'],
            ['SKU-001', 'Test Supplier', 'supplier', 'SUPP-SKU-001', 'Supplier Product Name'],
        ];

        $file = $this->createExcelFile($rows);

        // 3. Perform Import
        $response = $this->post(route('inventory.product-aliases.import'), [
            'file' => $file
        ]);

        // 4. Assertions
        if (session('errors')) {
            dump(session('errors')->all());
        }
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_partners', [
            'product_id' => $product->id,
            'partner_type' => Customer::class,
            'partner_id' => $customer->id,
            'alias_sku' => 'CUST-SKU-001',
            'alias_name' => 'Customer Product Name'
        ]);

        $this->assertDatabaseHas('product_partners', [
            'product_id' => $product->id,
            'partner_type' => Supplier::class,
            'partner_id' => $supplier->id,
            'alias_sku' => 'SUPP-SKU-001',
            'alias_name' => 'Supplier Product Name'
        ]);
    }

    /** @test */
    public function import_template_download()
    {
        $response = $this->get(route('inventory.product-aliases.template'));
        
        $response->assertStatus(200);
        // $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function import_template_download_with_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create dummy data
        $product = Product::factory()->create(['sku' => 'TEST-SKU-EXP']);
        
        $customer = Customer::create([
            'name' => 'TEST-CUST-EXP',
            'code' => 'CUST-EXP-001',
            'company_id' => $this->company->id,
            'email' => 'test@exp.com',
            'phone' => '123456',
            'address' => 'Test Address'
        ]);

        \App\Models\Inventory\ProductPartner::create([
            'product_id' => $product->id,
            'partner_type' => Customer::class,
            'partner_id' => $customer->id,
            'alias_sku' => 'ALIAS-SKU-EXP',
            'alias_name' => 'ALIAS-NAME-EXP',
        ]);

        $response = $this->get(route('inventory.product-aliases.template', ['with_data' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition', 'attachment; filename=product_aliases_data.xlsx');
    }

    private function createExcelFile(array $rows)
    {
        $content = '';
        foreach ($rows as $row) {
            $content .= implode(',', $row) . "\n";
        }

        return UploadedFile::fake()->createWithContent('aliases.csv', $content);
    }
}
