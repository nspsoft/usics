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
            if ($item->id == 111) {
                // Pre-debug state
                $this->info("DEBUG ITEM 111 BEFORE: Delivered: {$item->qty_delivered}");
            }

            // Use the model's standardized method to recalculate (it handles saving if dirty)
            // But we want to count updates.
            // recalculateTotals() uses saveQuietly() or save() inside. 
            // We can check isDirty AFTER calling it? No, because it saves inside.
            // So we should check if values changed by capturing before/after.
            
            $oldDelivered = $item->qty_delivered;
            $oldReturned = $item->qty_returned;
            $oldInvoiced = $item->qty_invoiced;
            
            $item->recalculateTotals();
            
            // Reload to get fresh values if needed, or rely on object references if save() updates them (it should)
            // Actually, let's just assume if recalculateTotals did its job, it saved.
            
            if (abs($item->qty_delivered - $oldDelivered) > 0.001 || 
                abs($item->qty_returned - $oldReturned) > 0.001 || 
                abs($item->qty_invoiced - $oldInvoiced) > 0.001) {
                
                $updatedCount++;
                if ($item->id == 111) {
                     $this->info("DEBUG ITEM 111 AFTER: Delivered: {$item->qty_delivered} (Updated!)");
                }
            } else {
                 if ($item->id == 111) {
                     $this->info("DEBUG ITEM 111 NO CHANGE: Delivered: {$item->qty_delivered}");
                 }
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculation Complete. Updated {$updatedCount} items.");
    }
}
