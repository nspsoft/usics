<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Journal;
use App\Models\Finance\Coa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceLedgerController extends Controller
{
    public function index(Request $request)
    {
        $query = Journal::with(['items.coa']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $journals = $query->orderBy('date', 'desc')
                          ->paginate(15)
                          ->withQueryString();

        return Inertia::render('Finance/Ledger', [
            'journals' => $journals,
            'filters' => $request->only(['search', 'start_date', 'end_date'])
        ]);
    }
}
