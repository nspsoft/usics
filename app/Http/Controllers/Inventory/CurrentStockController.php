<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrentStockController extends Controller
{
    /**
     * Display a listing of current stocks.
     */
    public function index(Request $request): Response
    {
        $query = ProductStock::query()
            ->with(['product', 'product.category', 'warehouse'])
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereHas('warehouse')
            ->selectRaw('MIN(product_stocks.id) as id, product_stocks.product_id, product_stocks.warehouse_id, SUM(qty_on_hand) as qty_on_hand, SUM(qty_reserved) as qty_reserved')
            ->groupBy('product_stocks.product_id', 'product_stocks.warehouse_id')
            ->when($request->search, function ($q, $search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('product_stocks.warehouse_id', $warehouse);
            })
            ->when($request->category, function ($q, $category) {
                $q->whereHas('product', function ($p) use ($category) {
                    $p->where('category_id', $category);
                });
            })
            ->addSelect([
                'on_order_qty' => \App\Models\PurchaseOrderItem::selectRaw('COALESCE(SUM(qty - qty_received), 0)')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->whereColumn('purchase_order_items.product_id', 'product_stocks.product_id')
                    ->whereColumn('purchase_orders.warehouse_id', 'product_stocks.warehouse_id')
                    ->whereIn('purchase_orders.status', ['ordered', 'partial'])
            ]);

        // Dynamic Sorting
        $sort = $request->input('sort', 'product_name');
        $direction = $request->input('direction', 'asc');

        if ($sort === 'product_name') {
            $query->orderBy('products.name', $direction);
        } elseif ($sort === 'product_sku') {
            $query->orderBy('products.sku', $direction);
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction);
        } elseif ($sort === 'qty_on_hand') {
            $query->orderBy('qty_on_hand', $direction);
        } elseif ($sort === 'available') {
            $query->orderByRaw('(SUM(qty_on_hand) - SUM(qty_reserved)) ' . $direction);
        } else {
            $query->orderBy('products.name', 'asc')
                  ->orderBy('warehouse_id', 'asc');
        }

        $stocks = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Stocks/Index', [
            'stocks' => $stocks,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'categories' => Category::where('type', 'product')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'warehouse_id', 'category', 'sort', 'direction']),
        ]);
    }
}
