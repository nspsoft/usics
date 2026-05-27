<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Coa;
use App\Models\Finance\JournalItem;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'as_of' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $asOf = isset($validated['as_of']) ? Carbon::parse($validated['as_of'])->startOfDay() : now()->startOfDay();

        // 1. AR (1120)
        $ar = $this->getAccountData(
            code: '1120',
            balanceFormula: 'debit - credit',
            direction: 'ar',
            asOf: $asOf,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            search: $validated['search'] ?? null,
        );
        
        // 2. AP (2110)
        $ap = $this->getAccountData(
            code: '2110',
            balanceFormula: 'credit - debit',
            direction: 'ap',
            asOf: $asOf,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            search: $validated['search'] ?? null,
        );

        return Inertia::render('Finance/Monitoring', [
            'filters' => [
                'as_of' => $asOf->toDateString(),
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'search' => $validated['search'] ?? null,
            ],
            'ar' => $ar,
            'ap' => $ap
        ]);
    }

    private function getAccountData(string $code, string $balanceFormula, string $direction, Carbon $asOf, ?string $startDate, ?string $endDate, ?string $search)
    {
        $coa = Coa::where('code', $code)->first();
        if (!$coa) {
            return [
                'balance' => 0,
                'transactions' => [],
                'open_items' => [],
                'aging' => [],
            ];
        }

        $balance = JournalItem::where('coa_id', $coa->id)->sum(DB::raw($balanceFormula));

        $openItemsQuery = JournalItem::query()
            ->where('journal_items.coa_id', $coa->id)
            ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->selectRaw('journals.id as journal_id, journals.date as date, journals.reference as reference, journals.description as description, SUM(journal_items.debit) as debit, SUM(journal_items.credit) as credit')
            ->groupBy('journals.id', 'journals.date', 'journals.reference', 'journals.description');

        if ($startDate && $endDate) {
            $openItemsQuery->whereBetween('journals.date', [$startDate, $endDate]);
        }

        if ($search) {
            $openItemsQuery->where(function ($q) use ($search) {
                $q->where('journals.reference', 'like', "%{$search}%")
                    ->orWhere('journals.description', 'like', "%{$search}%");
            });
        }

        $openItems = $openItemsQuery
            ->orderBy('journals.date', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($row) use ($direction, $asOf) {
                $net = $direction === 'ar'
                    ? ((float) $row->debit - (float) $row->credit)
                    : ((float) $row->credit - (float) $row->debit);

                $date = $row->date ? Carbon::parse($row->date)->startOfDay() : null;
                $ageDays = $date ? $date->diffInDays($asOf) : 0;

                if ($ageDays <= 30) {
                    $bucket = '0-30';
                } elseif ($ageDays <= 60) {
                    $bucket = '31-60';
                } elseif ($ageDays <= 90) {
                    $bucket = '61-90';
                } else {
                    $bucket = '>90';
                }

                return [
                    'journal_id' => $row->journal_id,
                    'date' => $row->date,
                    'reference' => $row->reference,
                    'description' => $row->description,
                    'balance' => $net,
                    'age_days' => $ageDays,
                    'bucket' => $bucket,
                    'ledger_url' => '/finance/ledger?search=' . urlencode((string) $row->reference),
                ];
            })
            ->filter(fn ($item) => abs((float) $item['balance']) > 0.0001)
            ->values();

        $agingTemplate = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '>90' => 0,
        ];

        $aging = $openItems->reduce(function ($carry, $item) {
            $carry[$item['bucket']] = ($carry[$item['bucket']] ?? 0) + (float) $item['balance'];
            return $carry;
        }, $agingTemplate);
        $aging['total'] = array_sum(array_intersect_key($aging, $agingTemplate));
        
        $transactions = JournalItem::where('coa_id', $coa->id)
            ->with(['journal'])
            ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->orderBy('journals.date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->journal->date,
                    'reference' => $item->journal->reference,
                    'description' => $item->journal->description,
                    'amount' => $item->debit > 0 ? $item->debit : $item->credit
                ];
            });

        return [
            'balance' => $balance,
            'transactions' => $transactions,
            'open_items' => $openItems->take(10)->values(),
            'aging' => $aging,
        ];
    }
}
