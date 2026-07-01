<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\MaterialConsumption;
use App\Models\ProductionEntry;
use App\Models\SalesInvoice;
use Carbon\Carbon;

class CostingController extends Controller
{
    public function production()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $rawMaterialCost = MaterialConsumption::whereMonth('consumption_date', $currentMonth)
            ->whereYear('consumption_date', $currentYear)
            ->sum('total_cost');

        $totalProduced = ProductionEntry::whereMonth('production_date', $currentMonth)
            ->whereYear('production_date', $currentYear)
            ->sum('qty_produced');
            
        $laborRate = 12500; 
        $directLaborCost = $totalProduced * $laborRate;

        $fixedOverhead = 25000000;
        $variableOverheadRate = 5000;
        $overheadCost = $totalProduced > 0 ? $fixedOverhead + ($totalProduced * $variableOverheadRate) : 0;
        
        $otherCosts = $totalProduced > 0 ? 15000000 : 0;

        $totalCogs = $rawMaterialCost + $directLaborCost + $overheadCost + $otherCosts;

        $rmPct = $totalCogs > 0 ? round(($rawMaterialCost / $totalCogs) * 100) : 0;
        $dlPct = $totalCogs > 0 ? round(($directLaborCost / $totalCogs) * 100) : 0;
        $foPct = $totalCogs > 0 ? round(($overheadCost / $totalCogs) * 100) : 0;
        $ocPct = $totalCogs > 0 ? round(($otherCosts / $totalCogs) * 100) : 0;

        $costElements = [
            [ 'name' => 'Raw Materials', 'value' => $rawMaterialCost, 'percentage' => $rmPct, 'color' => '#3b82f6' ],
            [ 'name' => 'Direct Labor', 'value' => $directLaborCost, 'percentage' => $dlPct, 'color' => '#8b5cf6' ],
            [ 'name' => 'Factory Overhead', 'value' => $overheadCost, 'percentage' => $foPct, 'color' => '#22d3ee' ],
            [ 'name' => 'Other Costs', 'value' => $otherCosts, 'percentage' => $ocPct, 'color' => '#64748b' ],
        ];

        return Inertia::render('Costing/Production', [
            'dynamicCostElements' => $costElements,
            'dynamicTotalValue' => $totalCogs,
        ]);
    }

    public function overhead()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $totalProduced = ProductionEntry::whereMonth('production_date', $currentMonth)
            ->whereYear('production_date', $currentYear)
            ->sum('qty_produced');

        $depreciation = 42000000;
        $power = $totalProduced * 1500;
        $maintenance = 13000000;
        $supervision = 8500000;
        
        $totalOverhead = $depreciation + $power + $maintenance + $supervision;
        
        $depPct = $totalOverhead > 0 ? round(($depreciation / $totalOverhead) * 100) : 0;
        $powPct = $totalOverhead > 0 ? round(($power / $totalOverhead) * 100) : 0;
        $mainPct = $totalOverhead > 0 ? round(($maintenance / $totalOverhead) * 100) : 0;
        $supPct = $totalOverhead > 0 ? round(($supervision / $totalOverhead) * 100) : 0;
        
        $costElements = [
            [ 'name' => 'Machinery Depreciation', 'value' => $depreciation, 'percentage' => $depPct, 'color' => '#f59e0b' ],
            [ 'name' => 'Factory Power & Utilities', 'value' => $power, 'percentage' => $powPct, 'color' => '#ef4444' ],
            [ 'name' => 'Machine Maintenance', 'value' => $maintenance, 'percentage' => $mainPct, 'color' => '#10b981' ],
            [ 'name' => 'Indirect Supervision', 'value' => $supervision, 'percentage' => $supPct, 'color' => '#ec4899' ],
        ];

        // Simulate absorption rate
        $absorption = $totalProduced > 5000 ? 98.2 : ($totalProduced > 0 ? 75.5 : 0);

        return Inertia::render('Costing/Overhead', [
            'dynamicCostElements' => $costElements,
            'dynamicTotalValue' => $totalOverhead,
            'factoryAbsorption' => $absorption,
        ]);
    }

    public function profitability()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Revenue from issued/paid invoices this month
        $revenue = SalesInvoice::whereIn('status', ['issued', 'paid'])
            ->whereMonth('invoice_date', $currentMonth)
            ->whereYear('invoice_date', $currentYear)
            ->sum('total');

        // Estimate total COGS for sold items (using global ratio for simulation)
        // Alternatively, calculate actual material + labor + overhead for this month
        $rawMaterialCost = MaterialConsumption::whereMonth('consumption_date', $currentMonth)->sum('total_cost');
        $totalProduced = ProductionEntry::whereMonth('production_date', $currentMonth)->sum('qty_produced');
        $cogs = $rawMaterialCost + ($totalProduced * 12500) + 25000000 + ($totalProduced * 5000);
        
        $opex = 170000000;
        $ebitda = $revenue - $cogs - $opex;

        // If revenue is 0, let's provide some realistic dummy or just 0
        $totalValue = max($revenue, 1);

        $revPct = 100;
        $cogsPct = round(($cogs / $totalValue) * 100);
        $opexPct = round(($opex / $totalValue) * 100);
        $ebitdaPct = round(($ebitda / $totalValue) * 100);

        $costElements = [
            [ 'name' => 'Gross Revenue', 'value' => $revenue, 'percentage' => $revPct, 'color' => '#10b981' ],
            [ 'name' => 'Manufacturing Cost (COGS)', 'value' => $cogs, 'percentage' => $cogsPct, 'color' => '#ef4444' ],
            [ 'name' => 'Operating Expenses (OPEX)', 'value' => $opex, 'percentage' => $opexPct, 'color' => '#f59e0b' ],
            [ 'name' => 'Net Profit Margin (EBITDA)', 'value' => $ebitda, 'percentage' => $ebitdaPct, 'color' => '#3b82f6' ],
        ];

        return Inertia::render('Costing/Profitability', [
            'dynamicCostElements' => $costElements,
            'dynamicTotalValue' => $ebitda,
            'netMarginEstimate' => $ebitdaPct,
        ]);
    }

    public function aiAnalyze(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|in:production,overhead,profitability',
            'cost_elements' => 'required|array',
            'total_value' => 'required|string',
        ]);

        try {
            $geminiService = new GeminiService();
            $result = $geminiService->analyzeCosting(
                $request->input('mode'),
                $request->input('cost_elements'),
                $request->input('total_value')
            );

            if (!$result) {
                throw new \Exception('AI return null response.');
            }

            return response()->json([
                'success' => true,
                'analysis' => $result['analysis'] ?? '',
                'score' => $result['score'] ?? 0.8,
                'leaks_detected' => $result['leaks_detected'] ?? 0,
                'recommendation' => $result['recommendation'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Costing Analysis Controller Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menganalisis costing dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
