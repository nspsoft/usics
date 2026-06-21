<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankStatementTransaction;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BankReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        // Fetch incoming Credit transactions (CR) - Sales
        $unreconciledSalesTransactions = BankStatementTransaction::unreconciled()
            ->where('type', 'CR')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Fetch outgoing Debit transactions (DB) - Purchase
        $unreconciledPurchaseTransactions = BankStatementTransaction::unreconciled()
            ->where('type', 'DB')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Fetch paginated reconciled transactions
        $reconciledTransactions = BankStatementTransaction::reconciled()
            ->with(['salesPayment.salesInvoice.customer', 'purchasePayment.invoice.supplier', 'createdBy'])
            ->orderBy('reconciled_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Fetch unpaid or partially paid Sales Invoices (balance > 0, status is not draft)
        $pendingInvoices = SalesInvoice::with(['customer', 'salesOrder'])
            ->whereNotIn('status', [SalesInvoice::STATUS_DRAFT, SalesInvoice::STATUS_PAID, SalesInvoice::STATUS_CANCELLED])
            ->where('balance', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();

        // Fetch unpaid or partially paid Purchase Invoices (supplier)
        $pendingPurchaseInvoices = PurchaseInvoice::with(['supplier', 'purchaseOrder'])
            ->whereNotIn('status', [PurchaseInvoice::STATUS_PAID, PurchaseInvoice::STATUS_CANCELLED])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Finance/Reconciliation/Index', [
            'unreconciledSalesTransactions' => $unreconciledSalesTransactions,
            'unreconciledPurchaseTransactions' => $unreconciledPurchaseTransactions,
            'reconciledTransactions' => $reconciledTransactions,
            'pendingInvoices' => $pendingInvoices,
            'pendingPurchaseInvoices' => $pendingPurchaseInvoices,
            'paymentMethods' => SalesPayment::getPaymentMethods(),
            'purchasePaymentMethods' => PurchasePayment::getPaymentMethods(),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
            'bank_name' => 'required|string|in:BCA,Mandiri,Generic',
        ]);

        try {
            $file = $request->file('file');
            $bankName = $request->input('bank_name');
            $transactions = $this->parseFile($file->getRealPath(), $bankName);

            if (empty($transactions)) {
                return back()->with('error', 'Tidak ada transaksi valid yang ditemukan di dalam file.');
            }

            $importedCount = 0;
            $skippedCount = 0;

            DB::transaction(function () use ($transactions, $bankName, &$importedCount, &$skippedCount) {
                foreach ($transactions as $t) {
                    $hash = BankStatementTransaction::generateHash(
                        $t['transaction_date'],
                        $t['description'],
                        $t['amount'],
                        $t['type']
                    );

                    // Check if already exists to prevent duplication
                    $exists = BankStatementTransaction::where('hash', $hash)->exists();

                    if (!$exists) {
                        BankStatementTransaction::create([
                            'transaction_date' => $t['transaction_date'],
                            'description' => $t['description'],
                            'amount' => $t['amount'],
                            'type' => $t['type'],
                            'reference_number' => $t['reference_number'] ?? null,
                            'bank_name' => $bankName,
                            'hash' => $hash,
                            'created_by' => auth()->id(),
                        ]);
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            });

            return back()->with('success', "Sukses mengimpor {$importedCount} transaksi baru. ({$skippedCount} transaksi duplikat dilewati).");

        } catch (\Exception $e) {
            Log::error('Bank Statement Import Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function match(Request $request)
    {
        $validated = $request->validate([
            'bank_transaction_id' => 'required|exists:bank_statement_transactions,id',
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $transaction = BankStatementTransaction::findOrFail($validated['bank_transaction_id']);
                
                if ($transaction->reconciled_at) {
                    throw new \Exception('Transaksi bank ini sudah direkonsiliasi.');
                }
                
                $invoice = SalesInvoice::findOrFail($validated['sales_invoice_id']);
                
                if ($invoice->balance <= 0) {
                    throw new \Exception('Invoice ini sudah lunas.');
                }

                // Payment amount is the minimum of transaction amount or invoice balance
                $paymentAmount = min($transaction->amount, $invoice->balance);

                // Create the payment record
                $payment = $invoice->payments()->create([
                    'payment_number' => SalesPayment::generatePaymentNumber(),
                    'amount' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'] ?? $transaction->reference_number ?? 'Bank Reconciled',
                    'bank_name' => $transaction->bank_name,
                    'notes' => $validated['notes'] ?? 'Dibuat otomatis via Rekonsiliasi Bank dari transaksi mutasi tanggal: ' . $transaction->transaction_date->format('Y-m-d'),
                    'created_by' => auth()->id(),
                ]);

                // Update invoice paid amount and status
                $invoice->recalculatePaidAmount();

                // Link statement transaction to payment and mark reconciled
                $transaction->update([
                    'reconciled_at' => now(),
                    'sales_payment_id' => $payment->id,
                ]);
            });

            return back()->with('success', 'Rekonsiliasi bank berhasil diselesaikan.');
        } catch (\Exception $e) {
            Log::error('Bank Statement Match Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal melakukan rekonsiliasi: ' . $e->getMessage());
        }
    }

    public function matchPurchase(Request $request)
    {
        $validated = $request->validate([
            'bank_transaction_id' => 'required|exists:bank_statement_transactions,id',
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $transaction = BankStatementTransaction::findOrFail($validated['bank_transaction_id']);
                
                if ($transaction->reconciled_at) {
                    throw new \Exception('Transaksi bank ini sudah direkonsiliasi.');
                }
                
                $invoice = PurchaseInvoice::findOrFail($validated['purchase_invoice_id']);
                
                $amountDue = max(0, $invoice->total_amount - $invoice->paid_amount);
                if ($amountDue <= 0) {
                    throw new \Exception('Invoice supplier ini sudah lunas.');
                }

                // Payment amount is the minimum of transaction amount or invoice amount due
                $paymentAmount = min($transaction->amount, $amountDue);

                // Create the payment record
                $payment = $invoice->payments()->create([
                    'payment_number' => PurchasePayment::generatePaymentNumber(),
                    'amount' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'] ?? $transaction->reference_number ?? 'Bank Reconciled',
                    'bank_name' => $transaction->bank_name,
                    'notes' => $validated['notes'] ?? 'Dibuat otomatis via Rekonsiliasi Bank (Mutasi Keluar) dari transaksi mutasi tanggal: ' . $transaction->transaction_date->format('Y-m-d'),
                    'created_by' => auth()->id(),
                ]);

                // Update invoice paid amount and status
                $invoice->recalculatePaidAmount();

                // Link statement transaction to purchase payment and mark reconciled
                $transaction->update([
                    'reconciled_at' => now(),
                    'purchase_payment_id' => $payment->id,
                ]);
            });

            return back()->with('success', 'Rekonsiliasi bank untuk supplier berhasil diselesaikan.');
        } catch (\Exception $e) {
            Log::error('Bank Statement Purchase Match Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal melakukan rekonsiliasi supplier: ' . $e->getMessage());
        }
    }

    public function bulkMatch(Request $request)
    {
        $validated = $request->validate([
            'matches' => 'required|array',
            'matches.*.bank_transaction_id' => 'required|exists:bank_statement_transactions,id',
            'matches.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $successCount = 0;

        try {
            DB::transaction(function () use ($validated, &$successCount) {
                foreach ($validated['matches'] as $match) {
                    $transaction = BankStatementTransaction::findOrFail($match['bank_transaction_id']);
                    
                    if ($transaction->reconciled_at) {
                        continue; // Skip already reconciled
                    }
                    
                    $invoice = SalesInvoice::findOrFail($match['sales_invoice_id']);
                    
                    if ($invoice->balance <= 0) {
                        continue; // Skip already paid
                    }

                    $paymentAmount = min($transaction->amount, $invoice->balance);

                    $payment = $invoice->payments()->create([
                        'payment_number' => SalesPayment::generatePaymentNumber(),
                        'amount' => $paymentAmount,
                        'payment_date' => $validated['payment_date'],
                        'payment_method' => $validated['payment_method'],
                        'reference' => $transaction->reference_number ?? 'Bulk Bank Reconciled',
                        'bank_name' => $transaction->bank_name,
                        'notes' => 'Dibuat otomatis via Rekonsiliasi Bank Massal dari transaksi mutasi tanggal: ' . $transaction->transaction_date->format('Y-m-d'),
                        'created_by' => auth()->id(),
                    ]);

                    $invoice->recalculatePaidAmount();

                    $transaction->update([
                        'reconciled_at' => now(),
                        'sales_payment_id' => $payment->id,
                    ]);

                    $successCount++;
                }
            });

            return back()->with('success', "Berhasil merekonsiliasi {$successCount} invoice customer secara massal.");
        } catch (\Exception $e) {
            Log::error('Bank Statement Bulk Match Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal melakukan rekonsiliasi massal: ' . $e->getMessage());
        }
    }

    public function bulkMatchPurchase(Request $request)
    {
        $validated = $request->validate([
            'matches' => 'required|array',
            'matches.*.bank_transaction_id' => 'required|exists:bank_statement_transactions,id',
            'matches.*.purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $successCount = 0;

        try {
            DB::transaction(function () use ($validated, &$successCount) {
                foreach ($validated['matches'] as $match) {
                    $transaction = BankStatementTransaction::findOrFail($match['bank_transaction_id']);
                    
                    if ($transaction->reconciled_at) {
                        continue; // Skip already reconciled
                    }
                    
                    $invoice = PurchaseInvoice::findOrFail($match['purchase_invoice_id']);
                    
                    $amountDue = max(0, $invoice->total_amount - $invoice->paid_amount);
                    if ($amountDue <= 0) {
                        continue; // Skip already paid
                    }

                    $paymentAmount = min($transaction->amount, $amountDue);

                    $payment = $invoice->payments()->create([
                        'payment_number' => PurchasePayment::generatePaymentNumber(),
                        'amount' => $paymentAmount,
                        'payment_date' => $validated['payment_date'],
                        'payment_method' => $validated['payment_method'],
                        'reference' => $transaction->reference_number ?? 'Bulk Bank Reconciled',
                        'bank_name' => $transaction->bank_name,
                        'notes' => 'Dibuat otomatis via Rekonsiliasi Bank Massal (Mutasi Keluar) dari transaksi mutasi tanggal: ' . $transaction->transaction_date->format('Y-m-d'),
                        'created_by' => auth()->id(),
                    ]);

                    $invoice->recalculatePaidAmount();

                    $transaction->update([
                        'reconciled_at' => now(),
                        'purchase_payment_id' => $payment->id,
                    ]);

                    $successCount++;
                }
            });

            return back()->with('success', "Berhasil merekonsiliasi {$successCount} invoice supplier secara massal.");
        } catch (\Exception $e) {
            Log::error('Bank Statement Bulk Purchase Match Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal melakukan rekonsiliasi supplier massal: ' . $e->getMessage());
        }
    }

    public function aiAnalyze(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array',
            'invoices' => 'required|array',
            'type' => 'required|string|in:sales,purchase',
        ]);

        try {
            $geminiService = new GeminiService();
            $result = $geminiService->analyzeBankReconciliation(
                $request->input('transactions'),
                $request->input('invoices'),
                $request->input('type')
            );

            return response()->json([
                'success' => true,
                'matches' => $result['matches'] ?? []
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reconciliation Analysis Controller Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menganalisis rekonsiliasi dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Parse Excel/CSV bank statement files using PhpSpreadsheet
     */
    private function parseFile(string $filePath, string $bankName): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        $transactions = [];
        
        if ($bankName === 'BCA') {
            $startParsing = false;
            foreach ($rows as $row) {
                if (isset($row[0]) && (str_contains(strtolower($row[0]), 'tanggal') || str_contains(strtolower($row[0]), 'date'))) {
                    $startParsing = true;
                    continue;
                }
                
                if ($startParsing) {
                    if (empty($row[0]) || str_contains(strtolower($row[0]), 'saldo') || str_contains(strtolower($row[0]), 'total')) {
                        break;
                    }
                    
                    $dateStr = trim($row[0]);
                    $descStr = trim($row[1] ?? '');
                    $branch = trim($row[2] ?? '');
                    $amountStr = trim($row[3] ?? '0');
                    $typeStr = strtoupper(trim($row[4] ?? 'CR')); // DB/CR
                    
                    $amount = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^\d\.\,]/', '', $amountStr));
                    
                    // Format date (e.g. DD/MM or DD-MMM)
                    $date = null;
                    if (preg_match('/^\d{2}[\/\-]\d{2}$/', $dateStr)) {
                        $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr . '/' . date('Y'));
                    } else {
                        try {
                            $date = \Carbon\Carbon::parse($dateStr);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    
                    $transactions[] = [
                        'transaction_date' => $date->format('Y-m-d'),
                        'description' => $descStr . ($branch ? " (Cab: {$branch})" : ""),
                        'amount' => $amount,
                        'type' => $typeStr === 'DB' ? 'DB' : 'CR',
                        'reference_number' => null,
                    ];
                } else {
                    // Fallback
                    if (isset($row[0]) && preg_match('/^\d{2}[\/\-]\d{2}$/', trim($row[0]))) {
                        $dateStr = trim($row[0]);
                        $descStr = trim($row[1] ?? '');
                        $amountStr = trim($row[3] ?? '0');
                        $typeStr = strtoupper(trim($row[4] ?? 'CR'));
                        $amount = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^\d\.\,]/', '', $amountStr));
                        
                        $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr . '/' . date('Y'));
                        
                        $transactions[] = [
                            'transaction_date' => $date->format('Y-m-d'),
                            'description' => $descStr,
                            'amount' => $amount,
                            'type' => $typeStr === 'DB' ? 'DB' : 'CR',
                            'reference_number' => null,
                        ];
                    }
                }
            }
        } elseif ($bankName === 'Mandiri') {
            $startParsing = false;
            foreach ($rows as $row) {
                if (isset($row[0]) && (str_contains(strtolower($row[0]), 'posting date') || str_contains(strtolower($row[0]), 'tanggal'))) {
                    $startParsing = true;
                    continue;
                }
                
                if ($startParsing) {
                    if (empty($row[0]) || str_contains(strtolower($row[0]), 'ending balance') || str_contains(strtolower($row[0]), 'total')) {
                        break;
                    }
                    
                    $dateStr = trim($row[0]);
                    $descStr = trim($row[2] ?? '');
                    $refStr = trim($row[3] ?? '');
                    $debitStr = trim($row[4] ?? '0');
                    $creditStr = trim($row[5] ?? '0');
                    
                    $debit = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^\d\.\,]/', '', $debitStr));
                    $credit = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^\d\.\,]/', '', $creditStr));
                    
                    $amount = 0;
                    $type = 'CR';
                    
                    if ($credit > 0) {
                        $amount = $credit;
                        $type = 'CR';
                    } elseif ($debit > 0) {
                        $amount = $debit;
                        $type = 'DB';
                    } else {
                        continue;
                    }
                    
                    try {
                        $date = \Carbon\Carbon::parse($dateStr);
                    } catch (\Exception $e) {
                        continue;
                    }
                    
                    $transactions[] = [
                        'transaction_date' => $date->format('Y-m-d'),
                        'description' => $descStr,
                        'amount' => $amount,
                        'type' => $type,
                        'reference_number' => $refStr,
                    ];
                }
            }
        } else {
            // Generic format
            $startParsing = false;
            foreach ($rows as $row) {
                if (isset($row[0]) && (str_contains(strtolower($row[0]), 'date') || str_contains(strtolower($row[0]), 'tanggal'))) {
                    $startParsing = true;
                    continue;
                }
                
                if ($startParsing) {
                    if (empty($row[0])) continue;
                    
                    $dateStr = trim($row[0]);
                    $descStr = trim($row[1] ?? '');
                    $amountStr = trim($row[2] ?? '0');
                    $refStr = trim($row[3] ?? '');
                    $typeStr = strtoupper(trim($row[4] ?? 'CR'));
                    
                    $amount = (float) str_replace([',', '.'], ['', '.'], preg_replace('/[^\d\.\,]/', '', $amountStr));
                    
                    try {
                        $date = \Carbon\Carbon::parse($dateStr);
                    } catch (\Exception $e) {
                        continue;
                    }
                    
                    $transactions[] = [
                        'transaction_date' => $date->format('Y-m-d'),
                        'description' => $descStr,
                        'amount' => $amount,
                        'type' => $typeStr === 'DB' ? 'DB' : 'CR',
                        'reference_number' => $refStr,
                    ];
                }
            }
        }
        
        return $transactions;
    }

    /**
     * Download Excel template for bank statement upload
     */
    public function downloadTemplate(string $format)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($format === 'BCA') {
            // BCA format template headers
            $headers = ['Tanggal', 'Keterangan Mutasi', 'Kode Cabang', 'Nominal Transfer', 'DB/CR'];
            $sampleRow = ['21/06', 'KREDIT TRANSFER DARI JOHN DOE INV/0015', '0015', '5000000', 'CR'];
            $sheet->fromArray([$headers, $sampleRow]);
            $filename = 'template_mutasi_bca.xlsx';
        } elseif ($format === 'Mandiri') {
            // Mandiri MCM format headers
            $headers = ['Posting Date', 'Value Date', 'Description', 'Reference No', 'Debit', 'Credit', 'Balance'];
            $sampleRow = ['2026-06-21', '2026-06-21', 'TRANSFER DARI PT ABC REKONSIL INV/0016', 'REF-987654', '0', '10000000', '10000000'];
            $sheet->fromArray([$headers, $sampleRow]);
            $filename = 'template_mutasi_mandiri.xlsx';
        } else {
            // Generic format template headers
            $headers = ['Date', 'Description', 'Amount', 'Reference', 'Type'];
            $sampleRow = ['2026-06-21', 'PEMBAYARAN INV/0017', '7500000', 'TRF-112233', 'CR'];
            $sheet->fromArray([$headers, $sampleRow]);
            $filename = 'template_mutasi_generic.xlsx';
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
