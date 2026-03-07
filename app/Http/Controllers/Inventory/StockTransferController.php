<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('transfer_number', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where(function ($sub) use ($warehouse) {
                    $sub->where('source_warehouse_id', $warehouse)
                        ->orWhere('destination_warehouse_id', $warehouse);
                });
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);

        $transfers = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => $transfers,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'warehouse_id', 'sort', 'direction']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'in_transit', 'label' => 'In Transit'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Transfers/Form', [
            'transfer' => null,
            'transferNumber' => StockTransfer::generateNumber(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products' => Product::active()
                ->stockManaged()
                ->select('id', 'sku', 'name', 'unit_id')
                ->with('unit:id,name,symbol')
                ->orderBy('name')
                ->get()
                ->each->setAppends([]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transfer_number' => 'required|string|max:30|unique:inv_stock_transfers,transfer_number',
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_requested' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($validated) {
            $transfer = StockTransfer::create([
                'transfer_number' => $validated['transfer_number'],
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'qty_requested' => $item['qty_requested'],
                    'qty_sent' => 0,
                    'qty_received' => 0,
                ]);
            }
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', 'Stock Transfer draft berhasil dibuat.');
    }

    public function show(StockTransfer $transfer): Response
    {
        $transfer->load([
            'sourceWarehouse',
            'destinationWarehouse',
            'items.product.unit',
            'createdBy',
            'receivedBy',
        ]);

        return Inertia::render('Inventory/Transfers/Show', [
            'transfer' => $transfer,
        ]);
    }

    /**
     * Ship: Draft → In Transit
     * Deducts stock from the source warehouse.
     */
    public function ship(StockTransfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return back()->with('error', 'Hanya transfer berstatus Draft yang bisa dikirim.');
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $transfer->source_warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $qtySend = $item->qty_requested;

                // Deduct from source
                $stock->adjustStock(
                    -$qtySend,
                    null,
                    StockMovement::TYPE_TRANSFER,
                    $transfer,
                    "Transfer OUT #{$transfer->transfer_number} ke {$transfer->destinationWarehouse->name}"
                );

                $item->update(['qty_sent' => $qtySend]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'shipped_at' => now(),
            ]);
        });

        return back()->with('success', 'Barang telah dikirim. Status: In Transit.');
    }

    /**
     * Receive: In Transit → Received
     * Adds stock to the destination warehouse.
     */
    public function receive(StockTransfer $transfer)
    {
        if ($transfer->status !== 'in_transit') {
            return back()->with('error', 'Hanya transfer berstatus In Transit yang bisa diterima.');
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $stock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $transfer->destination_warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $qtyReceive = $item->qty_sent;

                // Get cost from source warehouse stock for avg cost calc
                $sourceStock = ProductStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $transfer->source_warehouse_id)
                    ->first();
                $cost = $sourceStock ? $sourceStock->avg_cost : 0;

                // Add to destination
                $stock->adjustStock(
                    $qtyReceive,
                    $cost,
                    StockMovement::TYPE_TRANSFER,
                    $transfer,
                    "Transfer IN #{$transfer->transfer_number} dari {$transfer->sourceWarehouse->name}"
                );

                $item->update(['qty_received' => $qtyReceive]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        });

        return back()->with('success', 'Barang diterima. Transfer selesai.');
    }

    public function destroy(StockTransfer $transfer)
    {
        if ($transfer->status !== 'draft') {
            return back()->with('error', 'Hanya transfer berstatus Draft yang bisa dihapus.');
        }

        $transfer->delete();

        return redirect()->route('inventory.transfers.index')
            ->with('success', 'Stock Transfer dihapus.');
    }
}
