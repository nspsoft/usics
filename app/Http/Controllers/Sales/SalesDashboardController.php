<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesDashboardController extends Controller
{
    public function index(): Response
    {
        // 1. KPI Stats
        
        // Monthly Revenue (Confirmed/Processing/Shipped/Delivered SOs this month)
        $monthlyRevenue = SalesOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('total') ?? 0;

        // Order Count (Current Month)
        $orderCount = SalesOrder::whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();

        // Pending Quotations
        // Check if Quotation model exists (it should based on dir listing)
        $pendingQuotations = Quotation::whereIn('status', ['draft', 'pending'])->count();

        // Average Order Value (Current Month)
        $avgOrderValue = $orderCount > 0 ? $monthlyRevenue / $orderCount : 0;

        $stats = [
            'monthly_revenue' => $monthlyRevenue,
            'order_count' => $orderCount,
            'pending_quotations' => $pendingQuotations,
            'avg_order_value' => $avgOrderValue,
        ];

        // 2. Sales Trend (Last 6 Months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', order_date)" : "DATE_FORMAT(order_date, '%Y-%m')";

        $salesTrend = SalesOrder::select(
                DB::raw("$monthFormat as month"),
                DB::raw('SUM(total) as total')
            )
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('order_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 3. Status Distribution
        $statusDist = SalesOrder::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 4. Top Customers
        $topCustomers = SalesOrder::select('customers.name', DB::raw('SUM(sales_orders.total) as total_revenue'))
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereNotIn('sales_orders.status', ['cancelled', 'draft'])
            ->whereYear('sales_orders.order_date', now()->year)
            ->groupBy('customers.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // 5. Recent Orders
        $recentOrders = SalesOrder::with('customer')
            ->orderByDesc('order_date')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'so_number' => $o->so_number,
                'customer' => $o->customer->name ?? 'Unknown',
                'amount' => $o->total,
                'status' => $o->status,
                'date' => $o->order_date->format('d M'),
            ]);

        return Inertia::render('Sales/Dashboard', [
            'stats' => $stats,
            'salesTrend' => $salesTrend,
            'statusDist' => $statusDist,
            'topCustomers' => $topCustomers,
            'recentOrders' => $recentOrders,
        ]);
    }
}
