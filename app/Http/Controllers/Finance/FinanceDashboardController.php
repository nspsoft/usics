<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\JournalItem;
use App\Models\Finance\Coa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        // 1. Calculate Balance Sheet Summary (Liquidity Radar)
        $assets = $this->getAccountBalance('Asset');
        $liabilities = $this->getAccountBalance('Liability');
        $equity = $this->getAccountBalance('Equity');
        
        // Net Profit (Revenue - Expenses)
        $revenue = $this->getAccountBalance('Revenue');
        $expenses = $this->getAccountBalance('Expense'); // Includes COGS
        $netProfit = $revenue - $expenses;

        // Add Net Profit to Equity for display
        $totalEquity = $equity + $netProfit;

        // 2. Profit & Loss Structure (Donut)
        $cogs = $this->getAccountBalanceByCode('5100'); // COGS
        $opex = $expenses - $cogs;

        // 3. Cash Flow Trend (Last 30 Days)
        $cashCoa = Coa::where('code', '1110')->first();
        $cashFlow = collect();
        if ($cashCoa) {
            $cashFlow = JournalItem::where('coa_id', $cashCoa->id)
                ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                ->where('journals.date', '>=', Carbon::now()->subDays(30))
                ->selectRaw('DATE(journals.date) as date, SUM(debit) as inflow, SUM(credit) as outflow')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // 4. Monthly Performance (Revenue vs Expenses - Last 6 Months)
        $monthlyPerformance = JournalItem::whereHas('coa', function($q) {
                $q->whereIn('type', ['Revenue', 'Expense']);
            })
            ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->where('journals.date', '>=', Carbon::now()->subMonths(6))
            ->selectRaw('
                DATE_FORMAT(journals.date, "%Y-%m") as month, 
                SUM(CASE WHEN coas.type = "Revenue" THEN (credit - debit) ELSE 0 END) as revenue,
                SUM(CASE WHEN coas.type = "Expense" THEN (debit - credit) ELSE 0 END) as expenses
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        // 5. Asset Composition (Current Assets Breakdown)
        $assetComposition = JournalItem::whereHas('coa', function($q) {
                $q->whereIn('code', ['1110', '1120', '1130']); // Cash, AR, Inventory
            })
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->selectRaw('coas.name, SUM(debit - credit) as total')
            ->groupBy('coas.name')
            ->get();

        // 6. Expense Breakdown
        $expenseBreakdown = JournalItem::whereHas('coa', function($q) {
                $q->where('type', 'Expense')->where('code', '!=', '5100');
            })
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->selectRaw('coas.name as category, SUM(debit - credit) as total')
            ->groupBy('coas.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Finance/Dashboard', [
            'kpi' => [
                'total_assets' => $assets,
                'total_liabilities' => $liabilities,
                'total_equity' => $totalEquity,
                'net_profit' => $netProfit,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0,
                'current_ratio' => $liabilities > 0 ? round($assets / $liabilities, 2) : 0
            ],
            'structure' => [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'opex' => $opex
            ],
            'cash_flow' => [
                'dates' => $cashFlow->pluck('date'),
                'inflow' => $cashFlow->pluck('inflow'),
                'outflow' => $cashFlow->pluck('outflow')
            ],
            'performance' => [
                'months' => $monthlyPerformance->pluck('month'),
                'revenue' => $monthlyPerformance->pluck('revenue'),
                'expenses' => $monthlyPerformance->pluck('expenses')
            ],
            'asset_composition' => $assetComposition,
            'expense_breakdown' => $expenseBreakdown
        ]);
    }

    private function getAccountBalance($type)
    {
        // For Assets/Expenses: Debit + Credit - (Normal Balance Debit)
        // For Liability/Equity/Revenue: Credit - Debit (Normal Balance Credit)
        // Correction: 
        // Asset/Expense: Debit is positive.
        // Liability/Equity/Revenue: Credit is positive.
        
        $balance = JournalItem::whereHas('coa', function($q) use ($type) {
            $q->where('type', $type);
        })->sum(DB::raw('debit - credit'));

        if (in_array($type, ['Liability', 'Equity', 'Revenue'])) {
            return $balance * -1;
        }

        return $balance;
    }

    private function getAccountBalanceByCode($code)
    {
        $coa = Coa::where('code', $code)->first();
        if (!$coa) return 0;
        
        $balance = JournalItem::where('coa_id', $coa->id)->sum(DB::raw('debit - credit'));
        
        // Determine sign based on type
        if (in_array($coa->type, ['Liability', 'Equity', 'Revenue'])) {
            return $balance * -1;
        }
        return $balance;
    }
}
