<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDeliveryOrders extends Command
{
    protected $signature = 'sync:delivery-orders {--dry-run : Only show what would be created}';
    protected $description = 'Sync Sales Orders delivered quantity by creating missing Delivery Orders';

    public function handle()
    {
        $this->info('Starting Delivery Order Synchronization...');
        
        $systemUser = User::where('email', 'admin@admin.com')->first() ?? User::first();
        $dryRun = $this->option('dry-run');

        $sos = SalesOrder::whereIn('status', ['delivered', 'completed', 'partial', 'shipped'])
            ->with(['items', 'deliveryOrders.items', 'customer'])
            ->get();

        $createdCount = 0;

        foreach ($sos as $so) {
            $missingItems = [];
            
            foreach ($so->items as $item) {
                $qtyInSO = (float) $item->qty_delivered;
                $qtyInDOs = $so->deliveryOrders
                    ->whereIn('status', ['delivered', 'shipped'])
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('qty_delivered');

                $diff = $qtyInSO - $qtyInDOs;

                if ($diff > 0.0001) {
                    $missingItems[] = [
                        'item' => $item,
                        'qty' => $diff
                    ];
                }
            }

            if (!empty($missingItems)) {
                $this->info("SO #{$so->so_number}: Found " . count($missingItems) . " items with missing DO records.");
                
                if (!$dryRun) {
                    DB::transaction(function () use ($so, $missingItems, $systemUser, &$createdCount) {
                        $customer = $so->customer;
                        $custCode = $customer ? ($customer->code ?? 'GEN') : 'GEN';
                        $roman = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
                        $monthRoman = $roman[(int) date('n')];

                        try {
                            $doNumber = app(\App\Services\DocumentNumberService::class)->generate('delivery_order', [
                                'CUST_CODE' => $custCode,
                                'ROMAN_MONTH' => $monthRoman
                            ]);
                        } catch (\Exception $e) {
                            $doNumber = DeliveryOrder::generateDoNumber();
                        }

                        $do = DeliveryOrder::create([
                            'company_id' => $so->company_id ?? 1,
                            'do_number' => $doNumber,
                            'sales_order_id' => $so->id,
                            'customer_id' => $so->customer_id,
                            'warehouse_id' => $so->warehouse_id,
                            'delivery_date' => $so->order_date ?? now(),
                            'status' => 'delivered',
                            'notes' => 'SYSTEM-SYNC: Generated to match SO delivered quantity.',
                            'prepared_by' => $systemUser->id,
                            'delivered_by' => $systemUser->id,
                            'delivered_at' => now(),
                        ]);

                        foreach ($missingItems as $missing) {
                            $do->items()->create([
                                'sales_order_item_id' => $missing['item']->id,
                                'product_id' => $missing['item']->product_id,
                                'qty_ordered' => $missing['item']->qty,
                                'qty_delivered' => $missing['qty'],
                                'unit_id' => $missing['item']->unit_id,
                            ]);
                        }
                        $createdCount++;
                    });
                } else {
                    foreach ($missingItems as $missing) {
                        $this->line("  - Item [{$missing['item']->product->sku}]: Missing {$missing['qty']} units");
                    }
                }
            }
        }

        $this->info('---');
        if ($dryRun) {
            $this->info("Dry run complete. Found Sales Orders requiring sync.");
        } else {
            $this->info("Synchronization complete. Created {$createdCount} Delivery Orders.");
        }
        
        return 0;
    }
}
