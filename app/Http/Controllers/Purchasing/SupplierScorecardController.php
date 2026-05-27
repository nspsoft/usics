<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplierScorecardController extends Controller
{
    public function index(Request $request): Response
    {
        $period = (int) $request->input('months', 6);
        $since = now()->subMonths($period);

        // ── 1. Supplier Scorecards ──
        $suppliers = Supplier::active()
            ->withCount([
                'purchaseOrders as total_pos' => fn($q) => $q->where('created_at', '>=', $since)->where('status', '!=', 'cancelled'),
                'purchaseOrders as completed_pos' => fn($q) => $q->where('created_at', '>=', $since)->where('status', PurchaseOrder::STATUS_RECEIVED),
                'goodsReceipts as total_grs' => fn($q) => $q->where('created_at', '>=', $since),
            ])
            ->withSum([
                'purchaseOrders as total_spend' => fn($q) => $q->where('created_at', '>=', $since)->where('status', '!=', 'cancelled'),
            ], 'total')
            ->having('total_pos', '>', 0)
            ->orderByDesc('total_spend')
            ->limit(30)
            ->get()
            ->map(function ($supplier) use ($since) {
                // On-Time Delivery Rate
                $onTimeData = $this->calcOnTimeRate($supplier->id, $since);

                // Avg Fulfillment Time (days from PO order_date to GR receipt_date)
                $avgFulfillment = $this->calcAvgFulfillment($supplier->id, $since);

                // Return rate
                $returnData = $this->calcReturnRate($supplier->id, $since);

                // Overall score (weighted)
                $score = $this->calcOverallScore(
                    $onTimeData['rate'],
                    $returnData['rate'],
                    $avgFulfillment
                );

                return [
                    'id'               => $supplier->id,
                    'code'             => $supplier->code,
                    'name'             => $supplier->name,
                    'total_pos'        => $supplier->total_pos,
                    'completed_pos'    => $supplier->completed_pos,
                    'total_grs'        => $supplier->total_grs,
                    'total_spend'      => round($supplier->total_spend ?? 0, 2),
                    'on_time_rate'     => $onTimeData['rate'],
                    'on_time_count'    => $onTimeData['on_time'],
                    'late_count'       => $onTimeData['late'],
                    'avg_fulfillment'  => $avgFulfillment,
                    'return_rate'      => $returnData['rate'],
                    'return_count'     => $returnData['count'],
                    'overall_score'    => $score,
                    'grade'            => $this->scoreToGrade($score),
                ];
            })
            ->sortByDesc('overall_score')
            ->values();

        // ── 2. Summary KPIs ──
        $avgOnTime = $suppliers->avg('on_time_rate');
        $avgScore = $suppliers->avg('overall_score');
        $topSupplier = $suppliers->first();
        $totalSuppliers = $suppliers->count();

        // ── 3. Grade distribution ──
        $gradeDistribution = $suppliers->groupBy('grade')->map->count()->toArray();

        // ── 4. Spend by supplier (top 10) ──
        $spendBySupplier = $suppliers->take(10)->map(fn($s) => [
            'name'  => mb_substr($s['name'], 0, 20),
            'spend' => $s['total_spend'],
        ])->values();

        // ── 5. Monthly on-time trend ──
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', goods_receipts.created_at)" : "DATE_FORMAT(goods_receipts.created_at, '%Y-%m')";

        $onTimeTrend = DB::table('goods_receipts')
            ->join('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->select(
                DB::raw("$monthFormat as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN goods_receipts.receipt_date <= purchase_orders.expected_date THEN 1 ELSE 0 END) as on_time')
            )
            ->where('goods_receipts.created_at', '>=', $since)
            ->whereNotNull('purchase_orders.expected_date')
            ->whereNotNull('goods_receipts.receipt_date')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($r) => [
                'month'   => $r->month,
                'total'   => $r->total,
                'on_time' => $r->on_time,
                'rate'    => $r->total > 0 ? round(($r->on_time / $r->total) * 100, 1) : 0,
            ]);

        return Inertia::render('Purchasing/SupplierScorecard', [
            'suppliers'         => $suppliers,
            'stats'             => [
                'total_suppliers'  => $totalSuppliers,
                'avg_on_time'      => round($avgOnTime ?? 0, 1),
                'avg_score'        => round($avgScore ?? 0, 1),
                'top_supplier'     => $topSupplier['name'] ?? '-',
                'top_score'        => $topSupplier['overall_score'] ?? 0,
            ],
            'gradeDistribution' => $gradeDistribution,
            'spendBySupplier'   => $spendBySupplier,
            'onTimeTrend'       => $onTimeTrend,
            'period'            => $period,
        ]);
    }

    private function calcOnTimeRate(int $supplierId, Carbon $since): array
    {
        $grs = GoodsReceipt::where('supplier_id', $supplierId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('receipt_date')
            ->whereHas('purchaseOrder', fn($q) => $q->whereNotNull('expected_date'))
            ->with('purchaseOrder:id,expected_date')
            ->get();

        $onTime = 0;
        $late = 0;
        foreach ($grs as $gr) {
            if ($gr->purchaseOrder && $gr->receipt_date <= $gr->purchaseOrder->expected_date) {
                $onTime++;
            } else {
                $late++;
            }
        }

        $total = $onTime + $late;
        return [
            'rate'    => $total > 0 ? round(($onTime / $total) * 100, 1) : null,
            'on_time' => $onTime,
            'late'    => $late,
        ];
    }

    private function calcAvgFulfillment(int $supplierId, Carbon $since): ?float
    {
        $avg = GoodsReceipt::where('goods_receipts.supplier_id', $supplierId)
            ->where('goods_receipts.created_at', '>=', $since)
            ->whereNotNull('goods_receipts.receipt_date')
            ->whereHas('purchaseOrder')
            ->join('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
            ->selectRaw('AVG(DATEDIFF(goods_receipts.receipt_date, purchase_orders.order_date)) as avg_days')
            ->value('avg_days');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    private function calcReturnRate(int $supplierId, Carbon $since): array
    {
        $totalGRItems = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->where('goods_receipts.supplier_id', $supplierId)
            ->where('goods_receipts.created_at', '>=', $since)
            ->sum('goods_receipt_items.qty_received');

        $returnedQty = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_return_items.purchase_return_id', '=', 'purchase_returns.id')
            ->join('purchase_orders', 'purchase_returns.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.supplier_id', $supplierId)
            ->where('purchase_returns.created_at', '>=', $since)
            ->where('purchase_returns.status', 'confirmed')
            ->sum('purchase_return_items.qty');

        $rate = $totalGRItems > 0 ? round(($returnedQty / $totalGRItems) * 100, 1) : 0;

        return [
            'rate'  => $rate,
            'count' => (int) $returnedQty,
        ];
    }

    private function calcOverallScore(?float $onTimeRate, float $returnRate, ?float $avgFulfillment): float
    {
        // Weighted scoring: On-Time 50%, Return Rate 30%, Fulfillment Speed 20%
        $onTimeScore = ($onTimeRate ?? 50); // default 50 if no data
        $returnScore = max(0, 100 - ($returnRate * 10)); // 0% return = 100, 10% return = 0
        $speedScore = 100; // default
        if ($avgFulfillment !== null) {
            // < 3 days = 100, 7 days = 70, 14 days = 40, 30+ days = 10
            $speedScore = match (true) {
                $avgFulfillment <= 3  => 100,
                $avgFulfillment <= 7  => 85,
                $avgFulfillment <= 14 => 65,
                $avgFulfillment <= 21 => 45,
                $avgFulfillment <= 30 => 25,
                default               => 10,
            };
        }

        return round(($onTimeScore * 0.5) + ($returnScore * 0.3) + ($speedScore * 0.2), 1);
    }

    private function scoreToGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default      => 'F',
        };
    }
}
