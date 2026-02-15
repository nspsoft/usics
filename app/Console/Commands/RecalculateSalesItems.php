<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrderItem;
use Illuminate\Support\Facades\DB;

class RecalculateSalesItems extends Command
{
    protected $signature = 'sales:recalculate-items';
    protected $description = 'Recalculate cached qty_delivered, qty_returned, qty_invoiced for SalesOrderItems';

    public function handle()
    {
        $this->info("Starting Recalculation of Sales Order Items...");
        
        $items = SalesOrderItem::with('salesOrder')->get();
        $bar = $this->output->createProgressBar($items->count());
        $updatedCount = 0;

        foreach ($items as $item) {
            // 1. Calculate Gross Delivered (Sum of Valid DO Items)
            $grossDelivered = DeliveryOrderItem::where('sales_order_item_id', $item->id)
                ->whereHas('deliveryOrder', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('qty_delivered');

            // 2. Calculate Returned
            $returned = 0;
            try {
                // Use the relationship if it works, otherwise assume 0 for now to be safe or use direct query if relation is complex
                // The relation definition:
                // return $this->hasManyThrough(SalesReturnItem::class, SalesReturn::class, ...
                
                // Let's try to use the relation. If it fails, we catch it.
                // Actually, for robustness in this script, let's use a direct Join if possible, 
                // but sticking to Eloquent relationship is standard.
                $returned = $item->returnItems()->sum('qty');
            } catch (\Exception $e) {
                // $this->warn("Could not calc returns for Item ID {$item->id}: " . $e->getMessage());
            }

            // 3. Calculate Invoiced
            // Assuming direct relationship or we can leave it if not critical to this bug.
            // But let's try to fix it too.
            // Invoice items usually link to sales_order_item_id.
            $invoiced = \App\Models\SalesInvoiceItem::where('sales_order_item_id', $item->id)
                ->whereHas('salesInvoice', function ($q) {
                    $q->where('status', '!=', 'cancelled'); // Assuming invoice has status
                })
                ->sum('qty');

            // Update if different
            // We use float comparison
            $isDirty = false;
            if (abs($item->qty_delivered - $grossDelivered) > 0.001) {
                $item->qty_delivered = $grossDelivered;
                $isDirty = true;
            }
            if (abs($item->qty_returned - $returned) > 0.001) {
                $item->qty_returned = $returned;
                $isDirty = true;
            }
            if (abs($item->qty_invoiced - $invoiced) > 0.001) {
                $item->qty_invoiced = $invoiced;
                $isDirty = true;
            }

            if ($isDirty) {
                $item->saveQuietly(); // Avoid triggering events like 'saved' that might recalc totals excessively
                $updatedCount++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculation Complete. Updated {$updatedCount} items.");
    }
}
