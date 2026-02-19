<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Redirect Suppliers to their portal
        if ($user && $user->supplier_id) {
            return redirect()->route('portal.dashboard');
        }
        // Summary Stats
        $stats = [
            'products' => Product::where('is_active', true)->count(),
            'suppliers' => Supplier::where('is_active', true)->count(),
            'customers' => Customer::where('is_active', true)->count(),
            'lowStock' => $this->getLowStockCount(),
        ];

        // Recent Purchase Orders
        $recentPOs = PurchaseOrder::with(['supplier'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($po) => [
                'id' => $po->id,
                'number' => $po->po_number,
                'supplier' => $po->supplier->name ?? '-',
                'total' => $po->total,
                'status' => $po->status,
                'date' => $po->order_date->format('d M Y'),
            ]);

        // Recent Sales Orders
        $recentSOs = SalesOrder::with(['customer'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($so) => [
                'id' => $so->id,
                'number' => $so->so_number,
                'customer' => $so->customer->name ?? '-',
                'total' => $so->total,
                'status' => $so->status,
                'date' => $so->order_date->format('d M Y'),
            ]);

        // Active Work Orders
        $activeWorkOrders = WorkOrder::with(['product'])
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('priority', 'desc')
            ->orderBy('planned_end')
            ->limit(5)
            ->get()
            ->map(fn($wo) => [
                'id' => $wo->id,
                'number' => $wo->wo_number,
                'product' => $wo->product->name ?? '-',
                'qty_planned' => $wo->qty_planned,
                'qty_produced' => $wo->qty_produced,
                'progress' => $wo->progress_percent,
                'status' => $wo->status,
                'priority' => $wo->priority,
                'deadline' => $wo->planned_end->format('d M Y'),
            ]);

        // Low Stock Products
        $lowStockProducts = Product::with(['unit', 'stocks'])
            ->where('is_active', true)
            ->get()
            ->filter(fn($p) => $p->is_low_stock)
            ->take(5)
            ->map(fn($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'stock' => $p->total_stock,
                'min_stock' => $p->min_stock,
                'unit' => $p->unit->symbol ?? 'pcs',
            ]);

        // Order Stats by Status
        $poStatusCounts = PurchaseOrder::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $soStatusCounts = SalesOrder::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Monthly Sales (Last 6 months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dateFormat = $isSqlite ? "strftime('%Y-%m', order_date)" : "DATE_FORMAT(order_date, '%Y-%m')";

        $monthlySales = SalesOrder::select(
            DB::raw("$dateFormat as month"),
            DB::raw('SUM(total) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('order_date', '>=', now()->subMonths(6)->startOfMonth())
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Monthly Purchases (Last 6 months)
        $monthlyPurchases = PurchaseOrder::select(
            DB::raw("$dateFormat as month"),
            DB::raw('SUM(total) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('order_date', '>=', now()->subMonths(6)->startOfMonth())
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Stock Items by Type
        $stockByType = Product::select('product_type', DB::raw('count(*) as count'))
            ->where('is_active', true)
            ->groupBy('product_type')
            ->pluck('count', 'product_type');

        // Stock Movements (Last 7 Days)
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $movementDateFormat = $isSqlite ? "strftime('%Y-%m-%d', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')";

        $movements = \App\Models\StockMovement::select(
            DB::raw("$movementDateFormat as date"),
            'type',
            DB::raw('SUM(qty) as total')
        )
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('date', 'type')
            ->get()
            ->groupBy('date');
        
        $stockMovements = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $sevenDaysAgo->copy()->addDays($i)->format('Y-m-d');
            $dayMovements = $movements->get($date, collect());
            
            $stockMovements[] = [
                'date' => $sevenDaysAgo->copy()->addDays($i)->format('d M'),
                'in' => $dayMovements->where('type', 'in')->sum('total'),
                'out' => $dayMovements->where('type', 'out')->sum('total'),
            ];
        }

        // Top Customers by Sales Volume
        $topCustomers = SalesOrder::select('customer_id', DB::raw('SUM(total) as total_sales'))
            ->whereNotIn('status', ['cancelled'])
            ->with('customer')
            ->groupBy('customer_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->customer->name ?? 'Unknown',
                'total' => $item->total_sales,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentPOs' => $recentPOs,
            'recentSOs' => $recentSOs,
            'activeWorkOrders' => $activeWorkOrders,
            'lowStockProducts' => $lowStockProducts,
            'poStatusCounts' => $poStatusCounts,
            'soStatusCounts' => $soStatusCounts,
            'monthlySales' => $monthlySales,
            'monthlyPurchases' => $monthlyPurchases,
            'stockByType' => $stockByType,
            'stockMovements' => $stockMovements,
            'topCustomers' => $topCustomers,
        ]);
    }

    private function getLowStockCount(): int
    {
        return Product::where('is_active', true)
            ->get()
            ->filter(fn($p) => $p->is_low_stock)
            ->count();
    }
}
