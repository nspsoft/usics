<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use App\Models\Finance\Coa;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function sync(Request $request)
    {
        // 1. Validate that basic Chart of Accounts (COA) codes exist
        $requiredCoas = [
            '1120' => 'Accounts Receivable',
            '1130' => 'Inventory',
            '2110' => 'Accounts Payable',
            '4100' => 'Sales Revenue',
            '5100' => 'Cost of Goods Sold',
        ];

        $coas = Coa::whereIn('code', array_keys($requiredCoas))->get()->keyBy('code');

        $missing = array_diff(array_keys($requiredCoas), $coas->keys()->toArray());
        if (!empty($missing)) {
            $missingDetails = [];
            foreach ($missing as $code) {
                $missingDetails[] = "$code ({$requiredCoas[$code]})";
            }
            return redirect()->back()->with('error', 'Missing required Chart of Accounts (COA): ' . implode(', ', $missingDetails) . '. Please seed or create them first.');
        }

        $salesSyncedCount = 0;
        $purchaseSyncedCount = 0;

        DB::beginTransaction();
        try {
            // Get all existing journal references to avoid N+1 check in loop
            $existingReferences = Journal::pluck('reference')->filter()->toArray();
            $existingRefsMap = array_flip($existingReferences);

            // 2. Scan Sales Invoices (exclude draft and cancelled)
            $salesInvoices = SalesInvoice::whereNotIn('status', [
                SalesInvoice::STATUS_DRAFT,
                SalesInvoice::STATUS_CANCELLED
            ])->get();

            foreach ($salesInvoices as $invoice) {
                if (isset($existingRefsMap[$invoice->invoice_number])) {
                    continue; // Already synced
                }

                $amount = (float) $invoice->total;
                if ($amount <= 0) {
                    continue;
                }

                // Create Journal
                $journal = Journal::create([
                    'reference' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date ?? $invoice->created_at,
                    'description' => 'Sales Invoice Sync - ' . $invoice->invoice_number,
                    'status' => 'posted'
                ]);

                // Debit AR, Credit Sales
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['1120']->id,
                    'debit' => $amount,
                    'credit' => 0
                ]);
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['4100']->id,
                    'debit' => 0,
                    'credit' => $amount
                ]);

                // Debit COGS, Credit Inventory (simulate manufacturing cost at 70%)
                $cogsAmount = round($amount * 0.7, 2);
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['5100']->id,
                    'debit' => $cogsAmount,
                    'credit' => 0
                ]);
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['1130']->id,
                    'debit' => 0,
                    'credit' => $cogsAmount
                ]);

                $salesSyncedCount++;
                // Add to map dynamically to prevent duplicates in the same run if database had duplicate invoice numbers
                $existingRefsMap[$invoice->invoice_number] = true;
            }

            // 3. Scan Purchase Invoices (exclude cancelled)
            $purchaseInvoices = PurchaseInvoice::whereNotIn('status', [
                PurchaseInvoice::STATUS_CANCELLED
            ])->get();

            foreach ($purchaseInvoices as $invoice) {
                if (isset($existingRefsMap[$invoice->invoice_number])) {
                    continue; // Already synced
                }

                $amount = (float) $invoice->total_amount;
                if ($amount <= 0) {
                    continue;
                }

                // Create Journal
                $journal = Journal::create([
                    'reference' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date ?? $invoice->created_at,
                    'description' => 'Purchase Invoice Sync - ' . $invoice->invoice_number,
                    'status' => 'posted'
                ]);

                // Debit Inventory, Credit AP
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['1130']->id,
                    'debit' => $amount,
                    'credit' => 0
                ]);
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'coa_id' => $coas['2110']->id,
                    'debit' => 0,
                    'credit' => $amount
                ]);

                $purchaseSyncedCount++;
                $existingRefsMap[$invoice->invoice_number] = true;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ledger synchronization failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect()->back()->with('error', 'Synchronization failed: ' . $e->getMessage());
        }

        $totalSynced = $salesSyncedCount + $purchaseSyncedCount;
        if ($totalSynced > 0) {
            $msg = "Successfully synced $salesSyncedCount sales invoices and $purchaseSyncedCount purchase invoices to the general ledger.";
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('info', 'All invoices are already synchronized with the general ledger. No new entries created.');
    }
}
