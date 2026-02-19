<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementForecastController extends Controller
{
    public function index(Request $request): Response
    {
        $months = (int) $request->input('months', 3); // Lookback period for avg consumption

        // ── 1. Products needing reorder (below reorder point) ──
        $reorderAlerts = Product::where('is_purchased', true)
            ->where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->with('unit')
            ->get()
            ->map(function ($product) use ($months) {
                $totalStock = ProductStock::where('product_id', $product->id)
                    ->sum('qty_on_hand');

                // Skip if stock is above reorder point
                if ($totalStock > $product->reorder_point) {
                    return null;
                }

                // Calculate avg monthly consumption from stock movements (outbound)
                $avgConsumption = $this->getAvgMonthlyConsumption($product->id, $months);

                // Days of stock remaining
                $dailyConsumption = $avgConsumption / 30;
                $daysRemaining = $dailyConsumption > 0 ? round($totalStock / $dailyConsumption) : null;

                // Incoming PO qty (pending)
                $incomingQty = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                    ->where('purchase_order_items.product_id', $product->id)
                    ->whereIn('purchase_orders.status', ['ordered', 'partial', 'approved'])
                    ->sum(DB::raw('purchase_order_items.qty - purchase_order_items.qty_received'));

                return [
                    'id'               => $product->id,
                    'name'             => $product->name,
                    'sku'              => $product->sku,
                    'unit'             => $product->unit?->abbreviation ?? '-',
                    'current_stock'    => round($totalStock, 2),
                    'reorder_point'    => round($product->reorder_point, 2),
                    'reorder_qty'      => round($product->reorder_qty, 2),
                    'min_stock'        => round($product->min_stock, 2),
                    'lead_time_days'   => $product->lead_time_days,
                    'avg_consumption'  => round($avgConsumption, 2),
                    'days_remaining'   => $daysRemaining,
                    'incoming_qty'     => round($incomingQty, 2),
                    'shortage'         => round(max(0, $product->reorder_point - $totalStock), 2),
                    'urgency'          => $this->calculateUrgency($totalStock, $product->reorder_point, $product->min_stock, $daysRemaining, $product->lead_time_days),
                ];
            })
            ->filter()
            ->sortBy('urgency')
            ->values();

        // ── 2. Monthly spend trend (last 6 months) ──
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $spendTrend = PurchaseOrder::select(
                DB::raw("$monthFormat as month"),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as po_count')
            )
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── 3. Top consumed products (by stock movement volume) ──
        $consumptionDateFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $topConsumed = StockMovement::select(
                'product_id',
                DB::raw('SUM(ABS(qty)) as total_consumed')
            )
            ->whereIn('type', ['so_delivery', 'production_out'])
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('product_id')
            ->orderByDesc('total_consumed')
            ->limit(10)
            ->with('product:id,name,sku')
            ->get()
            ->map(fn($m) => [
                'product_name'   => $m->product?->name ?? 'Unknown',
                'sku'            => $m->product?->sku ?? '-',
                'total_consumed' => round($m->total_consumed, 2),
            ]);

        // ── 4. Consumption trend per month (aggregated) ──
        $consumptionTrend = StockMovement::select(
                DB::raw("$consumptionDateFormat as month"),
                DB::raw('SUM(ABS(qty)) as total_out')
            )
            ->whereIn('type', ['so_delivery', 'production_out'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── 5. KPI stats ──
        $stats = [
            'reorder_alerts'      => $reorderAlerts->count(),
            'critical_count'      => $reorderAlerts->where('urgency', 'critical')->count(),
            'total_monthly_spend' => PurchaseOrder::where('status', '!=', 'cancelled')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total') ?? 0,
            'active_pos'          => PurchaseOrder::whereIn('status', ['ordered', 'partial', 'approved'])->count(),
            'avg_lead_time'       => round(Product::where('is_purchased', true)->where('lead_time_days', '>', 0)->avg('lead_time_days') ?? 0, 1),
        ];

        return Inertia::render('Purchasing/ProcurementForecast', [
            'reorderAlerts'    => $reorderAlerts,
            'spendTrend'       => $spendTrend,
            'topConsumed'      => $topConsumed,
            'consumptionTrend' => $consumptionTrend,
            'stats'            => $stats,
            'lookbackMonths'   => $months,
        ]);
    }

    /**
     * Calculate average monthly consumption from outbound stock movements.
     */
    private function getAvgMonthlyConsumption(int $productId, int $months): float
    {
        $totalOut = StockMovement::where('product_id', $productId)
            ->whereIn('type', ['so_delivery', 'production_out'])
            ->where('created_at', '>=', now()->subMonths($months))
            ->sum(DB::raw('ABS(qty)'));

        return $months > 0 ? $totalOut / $months : 0;
    }

    /**
     * Determine urgency level based on stock vs reorder/min thresholds and lead time.
     */
    private function calculateUrgency(float $stock, float $reorderPoint, float $minStock, ?int $daysRemaining, int $leadTimeDays): string
    {
        // Critical: stock below min_stock or days remaining < lead_time
        if ($stock <= $minStock || ($daysRemaining !== null && $leadTimeDays > 0 && $daysRemaining <= $leadTimeDays)) {
            return 'critical';
        }

        // Warning: stock below reorder_point
        if ($stock <= $reorderPoint) {
            return 'warning';
        }

        return 'normal';
    }
}
