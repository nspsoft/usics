<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PurchaseInvoice::with(['supplier', 'purchaseOrder'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                        $pq->where('po_number', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->supplier, function ($q, $supplier) {
                $q->where('supplier_id', $supplier);
            });

        $sort = $request->input('sort', 'invoice_date');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction)
                  ->select('purchase_invoices.*');
        } elseif ($sort === 'po_number') {
            $query->leftJoin('purchase_orders', 'purchase_invoices.purchase_order_id', '=', 'purchase_orders.id')
                  ->orderBy('purchase_orders.po_number', $direction)
                  ->select('purchase_invoices.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $invoices = $query->paginate(20)->withQueryString();

        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => $invoices,
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $request->only(['search', 'status', 'supplier', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'unpaid', 'label' => 'Unpaid'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $supplierId = $request->supplier_id;
        $poId = $request->purchase_order_id;
        $grId = $request->goods_receipt_id;

        $unbilledReceipts = [];
        $selectedGR = null;

        if ($grId) {
            $selectedGR = GoodsReceipt::with(['items' => function ($q) {
                $q->whereRaw('qty_invoiced < qty_received')->with('product.unit');
            }, 'purchaseOrder'])->findOrFail($grId);

            $supplierId = $selectedGR->supplier_id;
            $poId = $selectedGR->purchase_order_id;
            $unbilledReceipts = [$selectedGR];
        } elseif ($supplierId) {
            $unbilledReceipts = GoodsReceipt::where('supplier_id', $supplierId)
                ->whereHas('items', function ($q) {
                    $q->whereRaw('qty_invoiced < qty_received');
                })
                ->with(['items' => function ($q) {
                    $q->whereRaw('qty_invoiced < qty_received')->with('product.unit');
                }, 'purchaseOrder'])
                ->get();
        }

        return Inertia::render('Purchasing/Invoices/Form', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'code']),
            'unbilledReceipts' => $unbilledReceipts,
            'preselectedSupplier' => $supplierId,
            'preselectedPO' => $poId,
            'preselectedGR' => $grId,
            'nextInvoiceNumber' => PurchaseInvoice::generateInvoiceNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'invoice_number' => 'required|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_percent' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_total' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.goods_receipt_item_id' => 'required|exists:goods_receipt_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $invoice = PurchaseInvoice::create([
                    'company_id' => auth()->user()?->company_id,
                    'invoice_number' => $validated['invoice_number'],
                    'purchase_order_id' => $validated['purchase_order_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'invoice_date' => $validated['invoice_date'],
                    'due_date' => $validated['due_date'],
                    'status' => 'unpaid',
                    'subtotal' => $validated['subtotal'],
                    'tax_percent' => $validated['tax_percent'],
                    'tax_amount' => $validated['tax_amount'],
                    'discount_total' => $validated['discount_total'],
                    'total_amount' => $validated['total_amount'],
                    'paid_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $grItem = \App\Models\GoodsReceiptItem::query()
                        ->with('goodsReceipt:id,supplier_id')
                        ->lockForUpdate()
                        ->findOrFail($item['goods_receipt_item_id']);

                    if ((int) $grItem->product_id !== (int) $item['product_id']) {
                        throw new \RuntimeException('Invalid invoice item: product mismatch.');
                    }

                    if ((int) ($grItem->goodsReceipt?->supplier_id ?? 0) !== (int) $validated['supplier_id']) {
                        throw new \RuntimeException('Invalid invoice item: supplier mismatch.');
                    }

                    $returnedQty = 0;
                    if (!empty($grItem->purchase_order_item_id)) {
                        $poItem = \App\Models\PurchaseOrderItem::query()
                            ->select(['id', 'qty_returned'])
                            ->find($grItem->purchase_order_item_id);
                        $returnedQty = (float) ($poItem?->qty_returned ?? 0);
                    }

                    $remaining = (float) $grItem->qty_received - (float) ($grItem->qty_invoiced ?? 0) - $returnedQty;
                    if ((float) $item['qty'] > $remaining + 0.0001) {
                        throw new \RuntimeException('Invalid invoice qty: exceeds remaining received qty.');
                    }

                    $invoice->items()->create([
                        'goods_receipt_item_id' => $item['goods_receipt_item_id'],
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Update qty_invoiced in Goods Receipt Item
                    $grItem->qty_invoiced += $item['qty'];
                    $grItem->save();
                }

                activity()
                    ->performedOn($invoice)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'invoice_number' => $invoice->invoice_number,
                        'supplier_id' => $invoice->supplier_id,
                        'purchase_order_id' => $invoice->purchase_order_id,
                        'total_amount' => (float) $invoice->total_amount,
                    ])
                    ->log('Created Purchase Invoice');
            });

            return redirect()->route('purchasing.invoices.index')
                ->with('success', 'Purchase Invoice created successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Invoice Creation Error: '.$e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());

            return back()->with('error', 'Error creating invoice: '.$e->getMessage())->withInput();
        }
    }

    public function show(PurchaseInvoice $invoice): Response
    {
        $invoice->load(['supplier', 'purchaseOrder', 'items.product.unit', 'createdBy', 'payments.createdBy']);

        return Inertia::render('Purchasing/Invoices/Show', [
            'invoice' => $invoice,
            'paymentMethods' => PurchasePayment::getPaymentMethods(),
            'nextPaymentNumber' => PurchasePayment::generatePaymentNumber(),
        ]);
    }

    /**
     * Record a payment for the invoice
     */
    public function recordPayment(Request $request, PurchaseInvoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->amount_due,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:transfer,cash,giro,cheque',
            'reference' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $invoice, $request) {
                // Handle file upload
                $attachmentPath = null;
                if ($request->hasFile('attachment')) {
                    $attachmentPath = $request->file('attachment')->store('payment-attachments', 'public');
                }

                // Create payment record
                $payment = $invoice->payments()->create([
                    'payment_number' => PurchasePayment::generatePaymentNumber(),
                    'amount' => $validated['amount'],
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'],
                    'bank_name' => $validated['bank_name'],
                    'account_number' => $validated['account_number'],
                    'attachment' => $attachmentPath,
                    'notes' => $validated['notes'],
                    'created_by' => auth()->id(),
                ]);

                // Update invoice paid amount and status
                $invoice->recalculatePaidAmount();
            });

            return back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment Recording Error: ' . $e->getMessage());
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payment record
     */
    public function deletePayment(PurchaseInvoice $invoice, PurchasePayment $payment)
    {
        try {
            DB::transaction(function () use ($invoice, $payment) {
                // Delete attachment if exists
                if ($payment->attachment) {
                    Storage::disk('public')->delete($payment->attachment);
                }

                // Delete payment
                $payment->delete();

                // Recalculate invoice
                $invoice->recalculatePaidAmount();
            });

            return back()->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment Deletion Error: ' . $e->getMessage());
            return back()->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }
}
