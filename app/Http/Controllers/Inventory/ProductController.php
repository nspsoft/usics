<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductExport;
use App\Imports\ProductImport;

use App\Exports\Template\ProductTemplateExport;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $query = Product::with(['category', 'unit', 'stocks.warehouse'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($q, $category) {
                $q->where('category_id', $category);
            })
            ->when($request->product_type, function ($q, $type) {
                $q->where('product_type', $type);
            })
            ->when($request->status !== null, function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            });

        // Dynamic Sorting
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        if ($sort === 'category_name') {
            $query->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $direction)
                  ->select('products.*');
        } elseif ($sort === 'unit_name') {
            $query->leftJoin('units', 'products.unit_id', '=', 'units.id')
                  ->orderBy('units.name', $direction)
                  ->select('products.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $products = $query->paginate(20)->withQueryString();

        // Attributes are now handled via $appends in the Product model
        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'category', 'product_type', 'status', 'sort', 'direction']),
            'productTypes' => [
                ['value' => 'raw_material', 'label' => 'Raw Material'],
                ['value' => 'wip', 'label' => 'Work in Progress'],
                ['value' => 'finished_good', 'label' => 'Finished Good'],
                ['value' => 'spare_part', 'label' => 'Spare Part'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('Inventory/Products/Form', [
            'product' => null,
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'type' => 'required|in:product,service,consumable',
            'product_type' => 'required|in:raw_material,wip,finished_good,spare_part',
            'unit_id' => 'nullable|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'is_manufactured' => 'boolean',
            'is_purchased' => 'boolean',
            'is_sold' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|max:10240', // 10MB max
        ]);

        $product = Product::create($validated);

        if ($request->hasFile('photo')) {
            $path = $this->handleImageUpload($request->file('photo'));
            $product->update(['image' => $path]);
        }

        // Create initial stock records for selected warehouses
        if ($request->has('initial_stocks')) {
            foreach ($request->initial_stocks as $stock) {
                if (!empty($stock['warehouse_id'])) {
                    $product->stocks()->create([
                        'warehouse_id' => $stock['warehouse_id'],
                        'qty_on_hand' => $stock['qty'] ?? 0,
                        'avg_cost' => $validated['cost_price'] ?? 0,
                    ]);
                }
            }
        }

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): Response
    {
        $product->load(['category', 'unit', 'stocks.warehouse', 'stocks.location', 'partners.partner']);

        return Inertia::render('Inventory/Products/Show', [
            'product' => $product,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        $product->load(['stocks.warehouse']);

        return Inertia::render('Inventory/Products/Form', [
            'product' => $product,
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'type' => 'required|in:product,service,consumable',
            'product_type' => 'required|in:raw_material,wip,finished_good,spare_part',
            'unit_id' => 'nullable|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'is_manufactured' => 'boolean',
            'is_purchased' => 'boolean',
            'is_sold' => 'boolean',
            'track_serial' => 'boolean',
            'track_batch' => 'boolean',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|max:10240',
            'remove_photo' => 'boolean',
        ]);

        $product->update($validated);

        if ($request->boolean('remove_photo')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $product->update(['image' => null]);
        } elseif ($request->hasFile('photo')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $this->handleImageUpload($request->file('photo'));
            $product->update(['image' => $path]);
        }

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        // Check if product can be safely deleted
        if (!$product->can_delete) {
            $message = $product->total_stock > 0 
                ? 'Cannot delete product with existing stock.' 
                : 'Cannot delete product that has been used in transactions.';
            return back()->with('error', $message);
        }

        $product->delete();

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function export()
    {
        return Excel::download(new ProductExport, 'products_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        Excel::import(new ProductImport($request->boolean('overwrite')), $request->file('file'));

        return back()->with('success', 'Products imported successfully.');
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\ProductDataExport, 'products_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new \App\Exports\Template\ProductTemplateExport, 'products_template.xlsx');
    }

    public function usage(Product $product)
    {
        return response()->json([
            'usage' => $product->getUsageSummary()
        ]);
    }

    /**
     * Handle image upload and compression
     */
    private function handleImageUpload($file): string
    {
        $filename = 'product_' . time() . '_' . uniqid() . '.webp';
        $path = 'product-photos/' . $filename;

        // Ensure directory exists
        if (!Storage::disk('public')->exists('product-photos')) {
            Storage::disk('public')->makeDirectory('product-photos');
        }

        // Resize and convert to WebP
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: 800, height: 800);

        Storage::disk('public')->put($path, (string) $image->toWebp(80));

        return $path;
    }
}
