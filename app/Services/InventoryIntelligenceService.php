<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Bom;
use App\Models\Inventory\ProductReclassMapping;
use App\Models\PurchaseOrderItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class InventoryIntelligenceService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Run the stock shortage analysis driven by unfulfilled Sales Orders.
     */
    public function analyze(array $options = []): array
    {
        // 1. Fetch active unfulfilled Sales Orders demand
        $demandItems = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereIn('sales_orders.status', ['confirmed', 'processing'])
            ->whereNull('sales_orders.deleted_at')
            ->whereRaw('sales_order_items.qty > sales_order_items.qty_delivered')
            ->selectRaw('sales_order_items.product_id, SUM(sales_order_items.qty - sales_order_items.qty_delivered) as total_demand')
            ->groupBy('sales_order_items.product_id')
            ->get();

        $shortageData = [];

        foreach ($demandItems as $item) {
            $product = Product::with(['category', 'unit'])->find($item->product_id);
            if (!$product) continue;

            // Apply optional filters (by category)
            if (!empty($options['category_id']) && $product->category_id != $options['category_id']) {
                continue;
            }

            // Calculate physical stock and reserved stock across all warehouses
            $stocks = ProductStock::where('product_id', $product->id)->get();
            $qtyOnHand = $stocks->sum('qty_on_hand');
            $qtyReserved = $stocks->sum('qty_reserved');
            $availableStock = $qtyOnHand - $qtyReserved;

            // Calculate quantities already on order (POs in progress)
            $incomingPoQty = PurchaseOrderItem::join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                ->where('purchase_order_items.product_id', $product->id)
                ->whereIn('purchase_orders.status', ['ordered', 'partial'])
                ->selectRaw('COALESCE(SUM(qty - qty_received), 0) as remaining')
                ->value('remaining') ?? 0;

            // Calculate quantities already in production (WOs in progress)
            $incomingWoQty = WorkOrder::where('product_id', $product->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->selectRaw('COALESCE(SUM(qty_planned - qty_produced), 0) as remaining')
                ->value('remaining') ?? 0;

            $totalIncoming = $incomingPoQty + $incomingWoQty;
            $deficit = $item->total_demand - ($availableStock + $totalIncoming);

            if ($deficit > 0) {
                // Fetch reclass mappings where this product is the TARGET
                $reclassMappings = ProductReclassMapping::where('target_product_id', $product->id)
                    ->where('is_active', true)
                    ->with('sourceProduct')
                    ->get();

                $reclassOptions = [];
                foreach ($reclassMappings as $mapping) {
                    $srcStocks = ProductStock::where('product_id', $mapping->source_product_id)->get();
                    $srcOnHand = $srcStocks->sum('qty_on_hand');
                    $srcReserved = $srcStocks->sum('qty_reserved');
                    $srcAvailable = $srcOnHand - $srcReserved;

                    $reclassOptions[] = [
                        'source_product_id' => $mapping->source_product_id,
                        'source_sku' => $mapping->sourceProduct->sku,
                        'source_name' => $mapping->sourceProduct->name,
                        'available_stock' => $srcAvailable,
                        'is_default' => $mapping->is_default,
                    ];
                }

                // Fetch BOM if it is a manufactured product
                $bomData = null;
                if ($product->is_manufactured) {
                    $bom = Bom::where('product_id', $product->id)->where('is_active', true)->with('components.product')->first();
                    if ($bom) {
                        $bomData = [
                            'bom_id' => $bom->id,
                            'bom_number' => $bom->bom_number,
                            'components' => $bom->components->map(function ($comp) {
                                $cStocks = ProductStock::where('product_id', $comp->product_id)->get();
                                $cOnHand = $cStocks->sum('qty_on_hand');
                                $cReserved = $cStocks->sum('qty_reserved');
                                
                                return [
                                    'product_id' => $comp->product_id,
                                    'sku' => $comp->product->sku,
                                    'name' => $comp->product->name,
                                    'bom_qty' => $comp->qty,
                                    'available_stock' => $cOnHand - $cReserved,
                                    'is_purchased' => $comp->product->is_purchased,
                                    'is_manufactured' => $comp->product->is_manufactured,
                                ];
                            })->toArray(),
                        ];
                    }
                }

                $historicalSuppliers = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                    ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                    ->where('purchase_order_items.product_id', $product->id)
                    ->whereNull('purchase_orders.deleted_at')
                    ->selectRaw('purchase_orders.supplier_id, suppliers.name as supplier_name, MIN(purchase_order_items.unit_price) as cheapest_price')
                    ->groupBy('purchase_orders.supplier_id', 'suppliers.name')
                    ->orderBy('cheapest_price', 'asc')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'supplier_id' => (int)$row->supplier_id,
                            'supplier_name' => $row->supplier_name,
                            'cheapest_price' => (float)$row->cheapest_price,
                        ];
                    })
                    ->toArray();

                $shortageData[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'type' => $product->product_type,
                    'is_purchased' => $product->is_purchased,
                    'is_manufactured' => $product->is_manufactured,
                    'production_type' => $product->is_manufactured ? (\App\Models\WorkOrder::where('product_id', $product->id)->where('production_type', 'subcontract')->exists() ? 'subcontract' : 'internal') : null,
                    'default_supplier_id' => $product->supplier_id,
                    'default_supplier_name' => optional($product->supplier_id ? \App\Models\Supplier::find($product->supplier_id) : null)->name,
                    'historical_suppliers' => $historicalSuppliers,
                    'demand_qty' => (float)$item->total_demand,
                    'available_stock' => (float)$availableStock,
                    'incoming_po_qty' => (float)$incomingPoQty,
                    'incoming_wo_qty' => (float)$incomingWoQty,
                    'net_shortage' => (float)$deficit,
                    'reclass_mappings' => $reclassOptions,
                    'bom' => $bomData,
                ];
            }
        }

        if (empty($shortageData)) {
            return [
                'reclassifications' => [],
                'purchase_orders' => [],
                'work_orders' => [],
                'analysis_summary' => 'Tidak ditemukan kekurangan stok produk berdasarkan Sales Order aktif saat ini. Semua pesanan dapat dipenuhi dengan persediaan yang ada.',
            ];
        }

        // Call Gemini Service
        $recommendations = $this->gemini->analyzeStockShortages($shortageData);

        if (!$recommendations || !is_array($recommendations)) {
            return [
                'reclassifications' => [],
                'purchase_orders' => [],
                'work_orders' => [],
                'analysis_summary' => 'Gagal memproses rekomendasi menggunakan AI. Silakan coba kembali.',
            ];
        }

        return $recommendations;
    }
}
