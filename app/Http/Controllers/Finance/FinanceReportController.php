<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\JournalItem;
use App\Models\Finance\Coa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function profitAndLoss(Request $request)
    {
        // Structure:
        // Revenue
        // - Sales Revenue
        // - Service Revenue
        // COGS
        // Gross Profit (calc)
        // Expenses
        // - Operational
        // Net Profit (calc)

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $revenue = $this->getCategoryBreakdown('Revenue', null, [], $startDate, $endDate);
        $cogs = $this->getCategoryBreakdown('Expense', '5100', [], $startDate, $endDate); // Specifically COGS
        $expenses = $this->getCategoryBreakdown('Expense', null, ['5100'], $startDate, $endDate); // Expenses excluding COGS

        $totalRevenue = $revenue->sum('total');
        $totalCogs = $cogs->sum('total');
        $grossProfit = $totalRevenue - $totalCogs;
        $totalExpenses = $expenses->sum('total');
        $netProfit = $grossProfit - $totalExpenses;

        return Inertia::render('Finance/Reports', [
            'pnl' => [
                'revenue' => $revenue,
                'total_revenue' => $totalRevenue,
                'cogs' => $cogs,
                'total_cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'expenses' => $expenses,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit
            ],
            'filters' => $request->only(['start_date', 'end_date'])
        ]);
    }

    private function getCategoryBreakdown($type, $specificCode = null, $excludedCodes = [], $startDate = null, $endDate = null)
    {
        $query = JournalItem::whereHas('coa', function($q) use ($type, $specificCode, $excludedCodes) {
            $q->where('type', $type);
            if ($specificCode) {
                $q->where('code', $specificCode);
            }
            if (!empty($excludedCodes)) {
                $q->whereNotIn('code', $excludedCodes);
            }
        });

        if ($startDate && $endDate) {
            $query->whereHas('journal', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        }

        // Sum Debit - Credit
        $results = $query->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->select('coas.name', 'coas.code', DB::raw('SUM(debit - credit) as total'))
            ->groupBy('coas.id', 'coas.name', 'coas.code')
            ->get();

        // Adjust sign
        return $results->map(function($item) use ($type) {
             if (in_array($type, ['Revenue', 'Liability', 'Equity'])) {
                 $item->total = $item->total * -1;
             }
             return $item;
        });
    }
}
