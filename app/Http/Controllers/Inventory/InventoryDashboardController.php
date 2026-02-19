<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryDashboardController extends Controller
{
    public function index(): Response
    {
        // 1. KPI Stats
        // Total Valuation: Sum of (total_stock * cost_price)
        // Since total_stock is an accessor, we might need to rely on ProductStock table or do a calculation.
        // For performance on large datasets, it's better to join stocks table.
        // But for now, let's use a simpler approach or a raw query if performance is key.
        // Let's iterate if dataset is small, or use DB query.
        
        // Approximate valuation using DB query for speed
        $valuation = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(product_stocks.qty_on_hand * products.cost_price) as total_value'))
            ->where('products.is_active', true)
            ->value('total_value') ?? 0;

        $activeItems = Product::where('is_active', true)->count();
        
        // Turnover placeholder (e.g., total outgoing qty in last 30 days)
        $turnoverQty = StockMovement::where('type', 'out')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('qty');
            
        // Warehouse Usage (Total items stored)
        $totalStoredItems = DB::table('product_stocks')->sum('qty_on_hand');

        $stats = [
            'total_valuation' => $valuation,
            'active_items' => $activeItems,
            'turnover_rate' => $turnoverQty, // displaying quantity for now as "velocity"
            'warehouse_usage' => $totalStoredItems,
        ];

        // 2. Stock Level Trends (Last 30 Days)
        // This is tricky without a daily snapshot table. 
        // We will mock this slightly by taking current stock and working backwards from movements, 
        // or just show movements trend for now which is safer and faster.
        // Let's show "Movement Activity" instead of "Stock Level History" to be accurate.
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dateFormat = $isSqlite ? "strftime('%Y-%m-%d', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')";
        
        $trends = StockMovement::select(
                DB::raw("$dateFormat as date"),
                DB::raw("SUM(CASE WHEN type = 'in' THEN qty ELSE 0 END) as incoming"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN qty ELSE 0 END) as outgoing")
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3. Stock by Category
        $stockByCategory = Product::select('categories.name', DB::raw('count(*) as count'))
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.is_active', true)
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 4. Stock by Warehouse
        $stockByWarehouse = Warehouse::select('warehouses.name', DB::raw('COALESCE(SUM(product_stocks.qty_on_hand), 0) as total_qty'))
            ->leftJoin('product_stocks', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get();

        // 5. Recent Movements
        $recentMovements = StockMovement::with(['product', 'warehouse'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'product' => $m->product->name ?? 'Unknown',
                'sku' => $m->product->sku ?? '-',
                'type' => $m->type,
                'qty' => $m->qty,
                'warehouse' => $m->warehouse->name ?? '-',
                'status' => 'COMPLETED', // Movements are usually completed when created
                'date' => $m->created_at->format('d M Y H:i'),
            ]);

        return Inertia::render('Inventory/Dashboard', [
            'stats' => $stats,
            'trends' => $trends,
            'stockByCategory' => $stockByCategory,
            'stockByWarehouse' => $stockByWarehouse,
            'recentMovements' => $recentMovements,
        ]);
    }
}
