<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Bom;
use App\Models\SalesForecast;
use App\Models\PurchaseRequest;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MapuController extends Controller
{
    public function index(): Response
    {
        // Default: planning is current month, arrival is 3 months later
        $planningMonth = Carbon::now()->format('Y-m');
        $arrivalMonth = Carbon::now()->addMonths(3)->format('Y-m');

        return Inertia::render('Purchasing/MapuPlanner', [
            'defaultPlanningMonth' => $planningMonth,
            'defaultArrivalMonth' => $arrivalMonth,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'planning_month' => 'required|date_format:Y-m',
            'arrival_month' => 'required|date_format:Y-m|after:planning_month',
        ]);

        $planningMonthStr = $request->input('planning_month');
        $arrivalMonthStr = $request->input('arrival_month');

        $planningMonth = Carbon::parse($planningMonthStr . '-01');
        $arrivalMonth = Carbon::parse($arrivalMonthStr . '-01');

        // Wait period is from planning month (P) to target month minus 1 (T-1)
        $waitPeriodEnd = $arrivalMonth->copy()->subMonth();
        
        $waitPeriodMonths = [];
        $period = CarbonPeriod::create($planningMonth, '1 month', $waitPeriodEnd);
        foreach ($period as $dt) {
            $waitPeriodMonths[] = $dt->format('Y-m');
        }

        // Get all Raw Material Coils (RM) that are purchased
        $rawMaterials = Product::active()
            ->where('is_purchased', true)
            ->where('product_type', 'raw_material')
            ->with('unit')
            ->orderBy('name')
            ->get();

        $results = [];

        foreach ($rawMaterials as $rm) {
            // 1. Gross Demand for Arrival Month (T)
            $arrivalPeriodStr = $arrivalMonth->format('Y-m-01');
            $arrivalForecasts = SalesForecast::whereDate('period', $arrivalPeriodStr)->get();
            
            $grossDemand = 0.0;
            foreach ($arrivalForecasts as $forecast) {
                $multiplier = $this->getRawMaterialMultiplier($forecast->product_id, $rm->id);
                if ($multiplier > 0) {
                    $grossDemand += $forecast->qty_forecast * $multiplier;
                }
            }

            // 2. Current Stock (S_P)
            $currentStock = ProductStock::where('product_id', $rm->id)->sum('qty_on_hand') ?? 0.0;

            // 3. Outstanding PO Qty (Expected arrival <= T-1)
            $outstandingPo = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->where('purchase_order_items.product_id', $rm->id)
                ->whereIn('purchase_orders.status', ['approved', 'ordered', 'partial', 'acknowledged'])
                ->where('purchase_orders.expected_date', '<=', $waitPeriodEnd->copy()->endOfMonth())
                ->sum(DB::raw('purchase_order_items.qty - purchase_order_items.qty_received')) ?? 0.0;

            // 4. Projected Consumption during Wait Period (P to T-1)
            $projectedConsumption = 0.0;
            foreach ($waitPeriodMonths as $monthStr) {
                $monthPeriodStr = $monthStr . '-01';
                $monthForecasts = SalesForecast::whereDate('period', $monthPeriodStr)->get();
                foreach ($monthForecasts as $forecast) {
                    $multiplier = $this->getRawMaterialMultiplier($forecast->product_id, $rm->id);
                    if ($multiplier > 0) {
                        $projectedConsumption += $forecast->qty_forecast * $multiplier;
                    }
                }
            }

            // 5. Projected Ending Stock before T (PE_T-1)
            $projectedEndingStock = $currentStock + $outstandingPo - $projectedConsumption;

            // 6. Safety Stock (SS)
            $safetyStock = $rm->min_stock ?? 0.0;

            // 7. Net Requirement (NR)
            // NR = GD + SS - PE
            $netRequirement = $grossDemand + $safetyStock - $projectedEndingStock;
            if ($netRequirement < 0) {
                $netRequirement = 0.0;
            }

            $results[] = [
                'product_id' => $rm->id,
                'sku' => $rm->sku,
                'name' => $rm->name,
                'unit' => $rm->unit?->abbreviation ?? 'kg',
                'gross_demand' => round($grossDemand, 2),
                'current_stock' => round($currentStock, 2),
                'outstanding_po' => round($outstandingPo, 2),
                'projected_consumption' => round($projectedConsumption, 2),
                'projected_ending_stock' => round($projectedEndingStock, 2),
                'safety_stock' => round($safetyStock, 2),
                'net_requirement' => round($netRequirement, 2),
                'recommendation' => $netRequirement > 0 ? 'ORDER' : 'OK',
            ];
        }

        return response()->json([
            'planning_month' => $planningMonthStr,
            'arrival_month' => $arrivalMonthStr,
            'wait_period' => $planningMonth->format('M Y') . ' - ' . $waitPeriodEnd->format('M Y'),
            'results' => $results,
        ]);
    }

    /**
     * Recursive BOM explosion to calculate raw material multiplier
     */
    protected function getRawMaterialMultiplier(int $parentId, int $targetRmId, float $currentMultiplier = 1.0, array $visited = []): float
    {
        if (in_array($parentId, $visited)) {
            return 0.0;
        }
        $visited[] = $parentId;

        $bom = Bom::where('product_id', $parentId)->active()->first();
        if (!$bom) {
            return 0.0;
        }

        $totalMultiplier = 0.0;
        foreach ($bom->components as $component) {
            $compQty = (float)($component->qty ?? 0.0);
            $scrapRate = (float)($component->scrap_rate ?? 0.0);
            
            $effectiveQty = $compQty * (1 + ($scrapRate / 100));
            $combinedMultiplier = $currentMultiplier * $effectiveQty;

            if ($component->product_id == $targetRmId) {
                $totalMultiplier += $combinedMultiplier;
            } else {
                $totalMultiplier += $this->getRawMaterialMultiplier($component->product_id, $targetRmId, $combinedMultiplier, $visited);
            }
        }

        return $totalMultiplier;
    }

    public function createPurchaseRequest(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'department' => 'required|string',
            'requester' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        DB::beginTransaction();
        try {
            $pr = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generatePrNumber(),
                'request_date' => $validated['request_date'],
                'department' => $validated['department'],
                'requester' => $validated['requester'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? 'Created via MAPU Import Planner',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $pr->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'description' => 'Suggested by MAPU Import Planner',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Request ' . $pr->pr_number . ' created successfully as draft.',
                'pr_number' => $pr->pr_number,
                'redirect_url' => route('purchasing.requests.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Purchase Request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
