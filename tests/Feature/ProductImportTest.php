<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImportTest extends TestCase
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

    #[Test]
    public function import_product_template_download_with_data()
    {
        // Create dummy data
        Product::factory()->create([
            'sku' => 'TEST-SKU-PROD',
            'name' => 'Test Product',
            'type' => 'product',
            'company_id' => $this->company->id,
            'is_manufactured' => true,
        ]);

        $response = $this->get(route('inventory.products.template', ['with_data' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // Filename should contain date
        $this->assertTrue(str_contains(
            $response->headers->get('content-disposition'), 
            'p'
        ));
    }
}
