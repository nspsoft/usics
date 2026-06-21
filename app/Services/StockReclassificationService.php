<?php

namespace App\Services;

use App\Models\Inventory\ProductReclassMapping;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockReclassification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockReclassificationService
{
    public function syncDraftTotals(StockReclassification $reclassification): void
    {
        $reclassification->load(['items.sourceProduct', 'items.targetProduct']);
        $totalQty = 0.0;
        $totalValue = 0.0;
        $totalSellValue = 0.0;
        $totalProfitNominal = 0.0;

        foreach ($reclassification->items as $item) {
            $costPerUnit = (float) ($item->sourceProduct->cost_price ?? 0);
            $sellPerUnit = (float) ($item->targetProduct->selling_price ?? 0);
            
            $lineTotal = round($item->qty * $costPerUnit, 2);
            $lineSell = round($item->qty * $sellPerUnit, 2);
            $profitNominal = $lineSell - $lineTotal;
            $profitPct = $lineTotal > 0 ? round(($profitNominal / $lineTotal) * 100, 4) : 0;

            $unitId = $item->targetProduct->unit_id ?? $item->sourceProduct->unit_id ?? $item->unit_id;

            $item->update([
                'unit_id' => $unitId,
                'cost_per_unit' => $costPerUnit,
                'selling_price_per_unit' => $sellPerUnit,
                'total_cost' => $lineTotal,
                'total_sell' => $lineSell,
                'profit_nominal' => $profitNominal,
                'profit_percentage' => $profitPct,
            ]);

            $totalQty += (float) $item->qty;
            $totalValue += $lineTotal;
            $totalSellValue += $lineSell;
            $totalProfitNominal += $profitNominal;
        }

        $totalProfitPercentage = $totalValue > 0 ? round(($totalProfitNominal / $totalValue) * 100, 4) : 0;

        $reclassification->update([
            'total_qty' => $totalQty,
            'total_value' => $totalValue,
            'total_sell_value' => $totalSellValue,
            'total_profit_nominal' => $totalProfitNominal,
            'total_profit_percentage' => $totalProfitPercentage,
        ]);
    }

    public function post(StockReclassification $reclassification): void
    {
        if ($reclassification->status !== StockReclassification::STATUS_DRAFT) {
            throw new RuntimeException('Hanya dokumen draft yang bisa diposting.');
        }

        $reclassification->load(['warehouse', 'targetWarehouse', 'items.sourceProduct', 'items.targetProduct', 'items.unit']);

        if ($reclassification->items->isEmpty()) {
            throw new RuntimeException('Dokumen reclass harus memiliki minimal 1 item.');
        }

        DB::transaction(function () use ($reclassification) {
            $mappingRows = ProductReclassMapping::active()
                ->get(['source_product_id', 'target_product_id', 'is_default'])
                ->groupBy('source_product_id');

            foreach ($reclassification->items as $index => $item) {
                $allowedTargets = $mappingRows->get((int) $item->source_product_id, collect())
                    ->pluck('target_product_id')
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($allowedTargets) && !in_array((int) $item->target_product_id, $allowedTargets, true)) {
                    throw new RuntimeException('Target product tidak sesuai mapping. Silakan periksa Reclass Mapping.');
                }
                if ((int) $item->source_product_id === (int) $item->target_product_id) {
                    throw new RuntimeException('Source product dan target product tidak boleh sama.');
                }
            }

            $requiredBySource = $reclassification->items
                ->groupBy('source_product_id')
                ->map(fn ($items) => (float) $items->sum('qty'));

            foreach ($requiredBySource as $productId => $qtyNeeded) {
                $stock = ProductStock::query()
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $reclassification->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                $availableQty = (float) ($stock?->qty_on_hand ?? 0);

                if ($availableQty < $qtyNeeded) {
                    $productName = optional($reclassification->items->firstWhere('source_product_id', (int) $productId)->sourceProduct)->name ?? 'Unknown Product';
                    throw new RuntimeException("Stok source tidak cukup untuk {$productName}. Butuh {$qtyNeeded}, tersedia {$availableQty}.");
                }
            }

            $totalQty = 0.0;
            $totalValue = 0.0;
            $totalSellValue = 0.0;
            $totalProfitNominal = 0.0;

            $targetWarehouseId = $reclassification->target_warehouse_id ?: $reclassification->warehouse_id;

            foreach ($reclassification->items as $item) {
                $sourceStock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->source_product_id,
                        'warehouse_id' => $reclassification->warehouse_id,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $targetStock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $item->target_product_id,
                        'warehouse_id' => $targetWarehouseId,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'qty_incoming' => 0,
                        'qty_outgoing' => 0,
                        'avg_cost' => 0,
                    ]
                );

                $sourceCost = (float) ($item->sourceProduct->cost_price ?? 0);
                $targetCost = (float) ($item->targetProduct->selling_price ?? 0);
                $lineTotal = round($item->qty * $sourceCost, 2);
                $lineSell = round($item->qty * $targetCost, 2);
                $profitNominal = $lineSell - $lineTotal;
                $profitPct = $lineTotal > 0 ? round(($profitNominal / $lineTotal) * 100, 4) : 0;
 
                $sourceNotes = "Reclass OUT #{$reclassification->reclass_number} ke {$item->targetProduct?->name}";
                $targetNotes = "Reclass IN #{$reclassification->reclass_number} dari {$item->sourceProduct?->name}";
 
                if ((int) $targetWarehouseId !== (int) $reclassification->warehouse_id) {
                    $tgtWhName = optional($reclassification->targetWarehouse)->name ?? 'Gudang Tujuan';
                    $srcWhName = optional($reclassification->warehouse)->name ?? 'Gudang Asal';
                    $sourceNotes .= " (Pindah ke {$tgtWhName})";
                    $targetNotes .= " (Pindah dari {$srcWhName})";
                }
 
                $sourceStock->adjustStock(
                    -1 * (float) $item->qty,
                    $sourceCost,
                    StockMovement::TYPE_RECLASS,
                    $reclassification,
                    $sourceNotes,
                    $reclassification->reclass_number
                );
 
                $targetStock->adjustStock(
                    (float) $item->qty,
                    $targetCost,
                    StockMovement::TYPE_RECLASS,
                    $reclassification,
                    $targetNotes,
                    $reclassification->reclass_number
                );
 
                $unitId = $item->targetProduct->unit_id ?? $item->sourceProduct->unit_id ?? $item->unit_id;

                $item->update([
                    'unit_id' => $unitId,
                    'cost_per_unit' => $sourceCost,
                    'selling_price_per_unit' => $targetCost,
                    'total_cost' => $lineTotal,
                    'total_sell' => $lineSell,
                    'profit_nominal' => $profitNominal,
                    'profit_percentage' => $profitPct,
                ]);
 
                $totalQty += (float) $item->qty;
                $totalValue += $lineTotal;
                $totalSellValue += $lineSell;
                $totalProfitNominal += $profitNominal;
            }

            $totalProfitPercentage = $totalValue > 0 ? round(($totalProfitNominal / $totalValue) * 100, 4) : 0;

            $reclassification->update([
                'status' => StockReclassification::STATUS_POSTED,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'total_sell_value' => $totalSellValue,
                'total_profit_nominal' => $totalProfitNominal,
                'total_profit_percentage' => $totalProfitPercentage,
            ]);
        });
    }
}
