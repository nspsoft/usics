<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingDashboardController extends Controller
{
    public function index(): Response
    {
        // 1. KPI Stats
        
        // Monthly Spend (Current Month)
        // Adjust column names based on actual schema: 'total'
        $monthlySpend = PurchaseOrder::where('status', '!=', 'cancelled')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total') ?? 0;

        // Open Orders (Ordered, Partial, Approved)
        $openOrders = PurchaseOrder::whereIn('status', ['ordered', 'partial', 'approved'])->count();

        // Pending Approvals (PRs)
        $pendingApprovals = PurchaseRequest::where('status', 'draft')->count();

        // Bug 9 fix: Calculate supplier performance from actual GR on-time delivery rate
        $supplierPerf = null;
        $totalGR = \App\Models\GoodsReceipt::where('status', 'completed')
            ->whereHas('purchaseOrder')
            ->whereYear('created_at', now()->year)
            ->count();
        if ($totalGR > 0) {
            $onTimeGR = \App\Models\GoodsReceipt::where('status', 'completed')
                ->whereHas('purchaseOrder', function ($q) {
                    $q->whereColumn('goods_receipts.receipt_date', '<=', 'purchase_orders.expected_date');
                })
                ->whereYear('goods_receipts.created_at', now()->year)
                ->count();
            $supplierPerf = round(($onTimeGR / $totalGR) * 100, 1);
        }

        $stats = [
            'monthly_spend' => $monthlySpend,
            'open_orders' => $openOrders,
            'pending_approvals' => $pendingApprovals,
            'supplier_performance' => $supplierPerf,
        ];

        // 2. Spend Trend (Last 6 Months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $spendTrend = PurchaseOrder::select(
                DB::raw("$monthFormat as month"),
                DB::raw('SUM(total) as total')
            )
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 3. Status Distribution (Procurement Cycle)
        $statusDist = PurchaseOrder::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 4. Top Suppliers
        $topSuppliers = PurchaseOrder::select('suppliers.name', DB::raw('SUM(purchase_orders.total) as total_spend'))
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->whereYear('purchase_orders.created_at', now()->year)
            ->groupBy('suppliers.name')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        // 5. Recent/Urgent Requests
        $recentRequests = PurchaseRequest::where('status', 'draft')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'request_number' => $r->pr_number, // Fixed Column name pr_number
                'requester' => $r->requester, // requester is a string attribute
                'description' => $r->notes ?? 'No description', // notes is the column name for description
                'date' => $r->created_at->format('d M'),
                // Bug 8 fix: Determine urgency based on age (>3 days old draft = urgent)
                'is_urgent' => $r->created_at->diffInDays(now()) >= 3,
            ]);

        return Inertia::render('Purchasing/Dashboard', [
            'stats' => $stats,
            'spendTrend' => $spendTrend,
            'statusDist' => $statusDist,
            'topSuppliers' => $topSuppliers,
            'recentRequests' => $recentRequests,
        ]);
    }
}
