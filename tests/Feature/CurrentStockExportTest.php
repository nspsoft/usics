<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Inventory\CurrentStockExport;

class CurrentStockExportTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $warehouse;
    protected $category;
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

        $this->category = Category::create([
            'name' => 'Test Category',
            'code' => 'CAT01',
            'type' => 'product',
            'company_id' => $this->company->id
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main WH',
            'code' => 'MWH01',
            'company_id' => $this->company->id
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product FG',
            'sku' => 'PROD-FG-001',
            'unit_id' => $unit->id,
            'category_id' => $this->category->id,
            'type' => 'finished_goods',
            'company_id' => $this->company->id,
            'min_stock' => 10,
            'reorder_point' => 20,
            'max_stock' => 100
        ]);

        // Create initial stock
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand' => 50,
            'qty_reserved' => 5
        ]);
    }

    public function test_export_current_stock_downloads_excel()
    {
        Excel::fake();

        $response = $this->get(route('inventory.stocks.export', [
            'search' => 'PROD-FG-001',
            'warehouse_id' => $this->warehouse->id,
            'category' => $this->category->id
        ]));

        $response->assertStatus(200);

        $filename = 'current_stock_' . date('Ymd_His') . '.xlsx';

        Excel::assertDownloaded($filename, function (CurrentStockExport $export) {
            $collection = $export->collection();
            $this->assertCount(1, $collection);
            $this->assertEquals($this->product->id, $collection->first()->product_id);
            return true;
        });
    }
}
