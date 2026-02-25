<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

use App\Exports\Purchasing\GoodsReceiptsExport;
use App\Exports\Template\GoodsReceiptTemplateExport;
use App\Imports\Purchasing\GoodsReceiptsImport;
use Maatwebsite\Excel\Facades\Excel;

class GoodsReceiptController extends Controller
{
    public function export()
    {
        return Excel::download(new GoodsReceiptsExport, 'goods_receipts.xlsx');
    }

    public function template()
    {
        return Excel::download(new GoodsReceiptTemplateExport, 'goods_receipt_import_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new GoodsReceiptsImport, $request->file('file'));
            return back()->with('success', 'Goods Receipts imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function index(Request $request): Response
    {
        $query = GoodsReceipt::with(['purchaseOrder', 'supplier', 'warehouse', 'receivedBy'])
            ->withCount('items')
            ->withSum('items', 'qty_received')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('grn_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'supplier_name') {
            $query->join('suppliers', 'goods_receipts.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $direction)
                  ->select('goods_receipts.*');
        } elseif ($sort === 'warehouse_name') {
            $query->join('warehouses', 'goods_receipts.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $direction)
                  ->select('goods_receipts.*');
        } elseif ($sort === 'po_number') {
            $query->leftJoin('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
                  ->orderBy('purchase_orders.po_number', $direction)
                  ->select('goods_receipts.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $receipts = $query->paginate(20)->withQueryString();

        return Inertia::render('Purchasing/Receipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'inspected', 'label' => 'Inspected'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $purchaseOrder = null;
        if ($request->po_id) {
            $purchaseOrder = PurchaseOrder::with(['items.product', 'supplier', 'warehouse'])->find($request->po_id);
        }

        return Inertia::render('Purchasing/Receipts/Create', [
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => PurchaseOrder::whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_ORDERED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIAL,
            ])->with('supplier')->orderByDesc('created_at')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()->with('unit')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'delivery_note_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_ordered' => 'required|numeric|min:0',
            'items.*.qty_received' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        // Quantity validation against PO
        if ($validated['purchase_order_id']) {
            $po = PurchaseOrder::with('items')->find($validated['purchase_order_id']);
            foreach ($validated['items'] as $item) {
                $poItem = $po->items->where('id', $item['po_item_id'])->first();
                if ($poItem) {
                    $remaining = $poItem->qty - ($poItem->qty_received - $poItem->qty_returned);
                    $allowedMax = round($remaining * 1.1); // 10% tolerance rounded to whole number
                    if ($item['qty_received'] > $allowedMax + 0.0001) { 
                        return back()->with('error', "Cannot receive more than 110% of remaining quantity for product: {$poItem->product->name} (Remaining: {$remaining}, Max allowed: " . $allowedMax . ")")->withInput();
                    }
                }
            }
        }

        DB::transaction(function () use ($validated) {
            $receipt = GoodsReceipt::create([
                'grn_number' => GoodsReceipt::generateGrnNumber(),
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'receipt_date' => $validated['receipt_date'],
                'delivery_note_number' => $validated['delivery_note_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'received_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $receipt->items()->create([
                    'purchase_order_item_id' => $item['po_item_id'],
                    'product_id' => $item['product_id'],
                    'qty_ordered' => $item['qty_ordered'],
                    'qty_received' => $item['qty_received'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }
        });

        return redirect()->route('purchasing.receipts.index')
            ->with('success', 'Goods Receipt created successfully.');
    }

    public function show(GoodsReceipt $receipt): Response
    {
        $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'receivedBy']);

        return Inertia::render('Purchasing/Receipts/Show', [
            'receipt' => $receipt,
        ]);
    }

    public function complete(GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return back()->with('error', 'Receipt is already completed.');
        }

        $receipt->complete();

        return back()->with('success', 'Goods Receipt completed and stock updated.');
    }

    public function destroy(GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return back()->with('error', 'Completed receipts cannot be deleted.');
        }

        $receipt->delete();

        return redirect()->route('purchasing.receipts.index')
            ->with('success', 'Goods Receipt deleted successfully.');
    }

    public function getPoItems(PurchaseOrder $order)
    {
        $order->load(['items.product', 'goodsReceipts.items']);

        $items = $order->items->map(function ($item) use ($order) {
            // Total Received (Sum from GR items)
            $receivedQty = $order->goodsReceipts->flatMap->items
                ->where('product_id', $item->product_id)
                ->sum('qty_received');
            
            // Total Returned (Sum from Return items linked to PO)
            $returnedQty = $item->qty_returned; // Using the cached field in PO Item

            // Effective Pending = Ordered - (Received - Returned)
            $remainingQty = max(0, $item->qty - ($receivedQty - $returnedQty));

            if ($remainingQty <= 0) {
                return null;
            }

            return [
                'po_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'qty_ordered' => $item->qty,
                'qty_received_total' => $receivedQty,
                'remaining_qty' => $remainingQty,
                'unit_cost' => $item->unit_price, // Default to PO price
            ];
        })->filter()->values();

        return response()->json([
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'items' => $items,
        ]);
    }

    public function print(GoodsReceipt $receipt)
    {
        return view('print.goods-receipt', [
            'receipt' => $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product.unit', 'receivedBy'])
        ]);
    }

    public function publicValidate($id)
    {
        $receipt = GoodsReceipt::with(['supplier', 'warehouse', 'items.product.unit', 'receivedBy', 'purchaseOrder'])
            ->findOrFail($id);

        return view('print.public-goods-receipt-validation', [
            'receipt' => $receipt,
            'isAuthenticated' => auth()->check(),
            'user' => auth()->user(),
        ]);
    }

    /**
     * Process confirmation from public QR scan page (requires authentication)
     */
    public function publicConfirmReceive(Request $request, $id)
    {
        // Must be authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan login untuk mengkonfirmasi penerimaan barang.');
        }

        $receipt = GoodsReceipt::with(['items', 'purchaseOrder', 'supplier'])->findOrFail($id);

        if ($receipt->status === 'completed') {
            return back()->with('error', 'Penerimaan barang sudah selesai diproses.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:goods_receipt_items,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'items.*.is_match' => 'nullable|boolean',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $receipt) {
            // Update items with actual received quantities
            foreach ($request->items as $itemData) {
                $item = $receipt->items()->find($itemData['id']);
                if ($item) {
                    $item->qty_received = $itemData['qty_received'];
                    $item->save();
                }
            }

            // Update notes
            if ($request->notes) {
                $receipt->notes = ($receipt->notes ? $receipt->notes . "\n" : '') . "[Konfirmasi] " . $request->notes;
            }
            $receipt->received_by = auth()->id();
            $receipt->save();

            // Complete the receipt (stock posting + PO update)
            $receipt->complete();

            // Send notification to supplier
            $this->notifySupplierOnReceive($receipt);
        });

        return back()->with('success', 'Barang berhasil diterima! Stok telah diperbarui dan notifikasi telah dikirim ke supplier.');
    }

    /**
     * Send notification to supplier when goods are received
     */
    private function notifySupplierOnReceive(GoodsReceipt $receipt)
    {
        try {
            $supplier = $receipt->supplier;
            if (!$supplier || !$supplier->phone) {
                return;
            }

            $message = "✅ *KONFIRMASI PENERIMAAN BARANG*\n\n";
            $message .= "Yth. {$supplier->name},\n\n";
            $message .= "Barang dengan Surat Jalan *{$receipt->delivery_note_number}* telah diterima di gudang kami.\n\n";
            $message .= "📦 *Detail Penerimaan:*\n";
            $message .= "• GRN: {$receipt->grn_number}\n";
            $message .= "• Tanggal: " . now()->format('d/m/Y H:i') . "\n";
            $message .= "• PO: " . ($receipt->purchaseOrder->po_number ?? '-') . "\n\n";
            
            $message .= "📋 *Item Diterima:*\n";
            foreach ($receipt->items as $item) {
                $productName = $item->product->name ?? $item->product_name ?? 'Unknown';
                $message .= "- {$productName}: " . number_format($item->qty_received, 0, ',', '.') . " " . ($item->product->unit->code ?? 'pcs') . "\n";
            }
            
            $message .= "\nTerima kasih atas kerjasamanya.\n\n";
            $message .= "_Pesan otomatis dari JICOS ERP_";

            // Send via Fonnte if configured
            $fonnteToken = config('services.fonnte.token');
            if ($fonnteToken) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => $fonnteToken
                ])->post('https://api.fonnte.com/send', [
                    'target' => $supplier->phone,
                    'message' => $message,
                ]);
            }
        } catch (\Exception $e) {
            // Log but don't fail the transaction
            \Illuminate\Support\Facades\Log::warning('Failed to send supplier notification: ' . $e->getMessage());
        }
    }

    // --- Inbound Scanner Methods ---

    public function scan()
    {
        return Inertia::render('Purchasing/Inbound/Scanner');
    }

    public function processScan(Request $request)
    {
        $request->validate(['barcode' => 'required']);
        
        // Try to find by GRN Number
        $gr = GoodsReceipt::where('grn_number', $request->barcode)->first();
        
        // Try to find by ID if barcode is just ID (unlikely but possible) or "GRN-..."
        if (!$gr && str_starts_with($request->barcode, 'DN:')) {
             $id = substr($request->barcode, 3);
             $gr = GoodsReceipt::find($id);
        }

        if (!$gr) {
             return back()->withErrors(['message' => 'Delivery Note not found.']);
        }

        if ($gr->status === 'completed') {
             return redirect()->route('purchasing.receipts.show', $gr->id)->with('info', 'This delivery has already been received.');
        }

        return redirect()->route('purchasing.receipts.check', $gr->id);
    }

    public function check(GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return redirect()->route('purchasing.receipts.show', $receipt->id);
        }

        $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product.unit']);

        return Inertia::render('Purchasing/Receipts/Check', [
            'receipt' => $receipt
        ]);
    }

    public function confirmReceive(Request $request, GoodsReceipt $receipt)
    {
        if ($receipt->status === 'completed') {
            return back()->with('error', 'Receipt is already completed.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:goods_receipt_items,id',
            'items.*.qty_received' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $receipt) {
            // Update items
            foreach ($request->items as $itemData) {
                $item = $receipt->items()->find($itemData['id']);
                if ($item) {
                    $item->qty_received = $itemData['qty_received'];
                    $item->save();
                }
            }

            // Update notes
            if ($request->notes) {
                $receipt->notes = $request->notes;
                $receipt->save();
            }

            // Complete and Stock Up
            $receipt->complete();
        });

        return redirect()->route('purchasing.receipts.show', $receipt->id)
            ->with('success', 'Goods received and stock updated successfully.');
    }
}
