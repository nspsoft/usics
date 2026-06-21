<?php
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use App\Models\Finance\Coa;
use Carbon\Carbon;

echo "Diagnosing Finance Dashboard Data...\n";

$journalCount = Journal::count();
$itemCount = JournalItem::count();
echo "Total Journals: {$journalCount}\n";
echo "Total Journal Items: {$itemCount}\n";

if ($journalCount > 0) {
    $minDate = Journal::min('date');
    $maxDate = Journal::max('date');
    echo "Journal Date Range: {$minDate} to {$maxDate}\n";
}

$coaCount = Coa::count();
echo "Total COAs: {$coaCount}\n";

// Let's check Cash COA
$cashCoa = Coa::where('code', '1110')->first();
if ($cashCoa) {
    echo "Cash COA found: ID {$cashCoa->id}, Name: {$cashCoa->name}, Code: {$cashCoa->code}\n";
    $itemsCount = JournalItem::where('coa_id', $cashCoa->id)->count();
    echo "Total Journal Items under Cash COA: {$itemsCount}\n";
    
    // Check cash flow query
    $cf = JournalItem::where('coa_id', $cashCoa->id)
        ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
        ->selectRaw('journals.date, debit, credit')
        ->limit(5)
        ->get();
    echo "Sample Cash Flow entries (first 5):\n" . json_encode($cf, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Cash COA (code 1110) NOT found!\n";
}

// Let's check monthly performance query
$perf = JournalItem::whereHas('coa', function($q) {
        $q->whereIn('type', ['Revenue', 'Expense']);
    })
    ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
    ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
    ->selectRaw('journals.date, coas.type, coas.code, debit, credit')
    ->limit(5)
    ->get();
echo "Sample Performance entries (first 5):\n" . json_encode($perf, JSON_PRETTY_PRINT) . "\n";

// Run exact dashboard queries and print results
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
echo "Dashboard Cash Flow Count (last 30 days): " . $cashFlow->count() . "\n";

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
echo "Dashboard Performance Count (last 6 months): " . $monthlyPerformance->count() . "\n";
