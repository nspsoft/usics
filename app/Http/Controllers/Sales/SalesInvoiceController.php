<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesInvoiceExport;
use App\Imports\SalesInvoiceImport;
use App\Exports\Template\SalesInvoiceTemplateExport;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesInvoice::with(['salesOrder.customer'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('salesOrder', function ($sq) use ($search) {
                        $sq->where('so_number', 'like', "%{$search}%")
                            ->orWhere('customer_po_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                    });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $sort = $request->input('sort', 'invoice_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->leftJoin('customers', 'sales_invoices.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('sales_invoices.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $invoices = $query->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'issued', 'label' => 'Issued'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function show(SalesInvoice $salesInvoice): Response
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $salesInvoice,
        ]);
    }

    public function print(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return view('print.invoice', ['invoice' => $salesInvoice]);
    }

    public function printV2(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return view('print.invoice-v2', ['invoice' => $salesInvoice]);
    }

    public function publicValidate($uuid)
    {
        $invoice = SalesInvoice::with(['salesOrder.customer', 'items.product', 'items.unit'])
            ->where('id', $uuid)
            ->firstOrFail();

        return view('print.public-invoice-validation', ['invoice' => $invoice]);
    }

    public function confirm(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be confirmed.');
        }

        $salesInvoice->update(['status' => 'issued']);

        return back()->with('success', 'Invoice confirmed and issued.');
    }

    public function revise(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'issued') {
            return back()->with('error', 'Only issued invoices can be revised.');
        }

        \DB::transaction(function () use ($salesInvoice) {
            // Logic to append/increment REV-X
            $currentNumber = $salesInvoice->invoice_number;
            
            // Check if it already has REV-
            if (preg_match('/\/REV-(\d+)$/', $currentNumber, $matches)) {
                $currentRev = (int)$matches[1];
                $nextRev = $currentRev + 1;
                $newNumber = preg_replace('/\/REV-\d+$/', "/REV-{$nextRev}", $currentNumber);
            } else {
                $newNumber = $currentNumber . "/REV-1";
            }

            $salesInvoice->update([
                'status' => 'draft',
                'invoice_number' => $newNumber
            ]);
        });

        return back()->with('success', 'Invoice revised. Status reverted to Draft.');
    }

    public function updateTaxAmount(Request $request, SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited.');
        }

        $validated = $request->validate([
            'tax_amount' => 'required|numeric|min:0',
        ]);

        try {
            \DB::transaction(function () use ($salesInvoice, $validated) {
                $salesInvoice->tax_amount = $validated['tax_amount'];
                $salesInvoice->total = $salesInvoice->subtotal + $salesInvoice->tax_amount - $salesInvoice->discount_amount;
                $salesInvoice->balance = $salesInvoice->total - $salesInvoice->paid_amount;
                $salesInvoice->save();
            });

            return back()->with('success', 'VAT amount updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating VAT: ' . $e->getMessage());
        }
    }

    public function recordPayment(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$salesInvoice->balance,
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        try {
            \DB::transaction(function () use ($salesInvoice, $validated) {
                $salesInvoice->addPayment($validated['amount']);

                // You could also create a Payment model here if needed in the future
            });

            return back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error recording payment: '.$e->getMessage());
        }
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        try {
            \DB::transaction(function () use ($salesInvoice) {
                // Return invoiced qty to SO items
                foreach ($salesInvoice->items as $item) {
                    $soItem = $item->salesOrderItem;
                    if ($soItem) {
                        // Prevent negative value if data was previously corrupted
                        $soItem->qty_invoiced = max(0, $soItem->qty_invoiced - $item->qty);
                        $soItem->save();
                    }

                    if ($item->delivery_order_id && $item->sales_order_item_id) {
                        $doItem = \App\Models\DeliveryOrderItem::where('delivery_order_id', $item->delivery_order_id)
                            ->where('sales_order_item_id', $item->sales_order_item_id)
                            ->first();

                        if ($doItem) {
                            // Prevent negative value
                            $doItem->qty_invoiced = max(0, $doItem->qty_invoiced - $item->qty);
                            $doItem->save();
                        }
                    }
                }
                $salesInvoice->delete();
            });

            return back()->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting invoice: '.$e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new SalesInvoiceExport, 'sales_invoices_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new SalesInvoiceImport, $request->file('file'));
            return back()->with('success', 'Sales Invoices imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function template()
    {
        return Excel::download(new SalesInvoiceTemplateExport, 'sales_invoices_import_template.xlsx');
    }
}
