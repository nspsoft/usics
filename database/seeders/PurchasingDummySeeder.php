<?php

namespace Database\Seeders;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchasingDummySeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->value('id');
        $suppliers = Supplier::where('is_active', true)->pluck('id')->toArray();
        $products  = Product::limit(40)->get();
        $warehouse = Warehouse::first();
        $user      = User::first();
        $defaultUnit = Unit::first();

        if (empty($suppliers) || $products->isEmpty() || !$warehouse || !$user) {
            $this->command->warn('Missing required data (suppliers/products/warehouse/user). Skipping.');
            return;
        }

        $this->command->info("Seeding purchasing dummy data...");
        $this->command->info("  Suppliers: " . count($suppliers));
        $this->command->info("  Products: " . $products->count());

        $statuses   = ['received', 'received', 'received', 'completed', 'partial', 'ordered', 'approved'];
        $currencies = ['IDR'];
        $now        = Carbon::now();

        $poCount = 0;
        $grCount = 0;

        // Generate POs distributed across the last 6 months
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $monthStart = $now->copy()->subMonths($monthOffset)->startOfMonth();
            $monthEnd   = $now->copy()->subMonths($monthOffset)->endOfMonth();
            if ($monthEnd->gt($now)) $monthEnd = $now->copy();

            // 15-25 POs per month
            $posPerMonth = rand(15, 25);

            for ($i = 0; $i < $posPerMonth; $i++) {
                $supplierId  = $suppliers[array_rand($suppliers)];
                $orderDate   = $monthStart->copy()->addDays(rand(0, $monthStart->diffInDays($monthEnd)));
                $expectedDate = $orderDate->copy()->addDays(rand(3, 21));
                $status      = $statuses[array_rand($statuses)];

                // Generate PO number
                $poNumber = sprintf('PO-%s-%04d', $orderDate->format('Ym'), rand(1000, 9999));

                // Ensure unique PO number
                while (PurchaseOrder::where('po_number', $poNumber)->exists()) {
                    $poNumber = sprintf('PO-%s-%04d', $orderDate->format('Ym'), rand(1000, 9999));
                }

                $po = PurchaseOrder::create([
                    'company_id'       => $companyId,
                    'po_number'        => $poNumber,
                    'supplier_id'      => $supplierId,
                    'warehouse_id'     => $warehouse->id,
                    'order_date'       => $orderDate,
                    'expected_date'    => $expectedDate,
                    'status'           => $status,
                    'currency'         => 'IDR',
                    'exchange_rate'    => 1,
                    'subtotal'         => 0,
                    'discount_percent' => rand(0, 5),
                    'discount_amount'  => 0,
                    'tax_percent'      => 11,
                    'tax_amount'       => 0,
                    'total'            => 0,
                    'notes'            => 'Dummy PO for dashboard demo',
                    'created_by'       => $user->id,
                    'approved_by'      => in_array($status, ['approved','ordered','partial','received','completed']) ? $user->id : null,
                    'approved_at'      => in_array($status, ['approved','ordered','partial','received','completed']) ? $orderDate->copy()->addDay() : null,
                    'created_at'       => $orderDate,
                    'updated_at'       => $orderDate,
                ]);

                // 1-5 items per PO
                $itemCount = rand(1, 5);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $subtotal = 0;

                foreach ($selectedProducts as $product) {
                    $qty       = rand(10, 500);
                    $unitPrice = rand(5000, 500000);
                    $lineTotal = $qty * $unitPrice;
                    $qtyReceived = in_array($status, ['received', 'completed']) ? $qty : ($status === 'partial' ? rand(1, $qty - 1) : 0);

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id'        => $product->id,
                        'description'       => $product->name,
                        'qty'               => $qty,
                        'unit_id'           => $product->unit_id ?? $defaultUnit?->id,
                        'unit_price'        => $unitPrice,
                        'discount_percent'  => 0,
                        'discount_amount'   => 0,
                        'subtotal'          => $lineTotal,
                        'qty_received'      => $qtyReceived,
                        'qty_returned'      => 0,
                    ]);
                    $subtotal += $lineTotal;
                }

                // Recalc totals
                $discountAmt = $subtotal * ($po->discount_percent / 100);
                $afterDiscount = $subtotal - $discountAmt;
                $taxAmt = $afterDiscount * (11 / 100);
                $total = $afterDiscount + $taxAmt;

                $po->update([
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discountAmt,
                    'tax_amount'      => $taxAmt,
                    'total'           => $total,
                ]);

                $poCount++;

                // Create GR for received/completed/partial POs
                if (in_array($status, ['received', 'completed', 'partial'])) {
                    // Vary GR receipt date: some on-time, some late
                    $daysVariance = rand(-5, 10); // negative = early, positive = late
                    $receiptDate  = $expectedDate->copy()->addDays($daysVariance);
                    if ($receiptDate->gt($now)) $receiptDate = $now->copy();

                    $grnNumber = sprintf('GRN-%s-%04d', $receiptDate->format('Ym'), rand(1000, 9999));
                    while (GoodsReceipt::where('grn_number', $grnNumber)->exists()) {
                        $grnNumber = sprintf('GRN-%s-%04d', $receiptDate->format('Ym'), rand(1000, 9999));
                    }

                    $gr = GoodsReceipt::create([
                        'company_id'          => $companyId,
                        'grn_number'          => $grnNumber,
                        'purchase_order_id'   => $po->id,
                        'supplier_id'         => $supplierId,
                        'warehouse_id'        => $warehouse->id,
                        'receipt_date'        => $receiptDate,
                        'delivery_note_number'=> 'DN-' . rand(10000, 99999),
                        'status'              => $status === 'partial' ? 'received' : 'completed',
                        'notes'               => 'Dummy GR',
                        'received_by'         => $user->id,
                        'created_at'          => $receiptDate,
                        'updated_at'          => $receiptDate,
                    ]);

                    // GR items + stock movements
                    foreach ($po->items as $poItem) {
                        if ($poItem->qty_received <= 0) continue;

                        $qtyRejected = rand(0, 100) < 10 ? rand(1, max(1, (int)($poItem->qty_received * 0.05))) : 0;

                        GoodsReceiptItem::create([
                            'goods_receipt_id'       => $gr->id,
                            'purchase_order_item_id' => $poItem->id,
                            'product_id'             => $poItem->product_id,
                            'qty_ordered'            => $poItem->qty,
                            'qty_received'           => $poItem->qty_received,
                            'qty_rejected'           => $qtyRejected,
                            'unit_id'                => $poItem->unit_id,
                            'unit_cost'              => $poItem->unit_price,
                            'notes'                  => null,
                        ]);

                        // Stock movement (consumption = out, receiving = in)
                        StockMovement::create([
                            'product_id'         => $poItem->product_id,
                            'warehouse_id'       => $warehouse->id,
                            'qty'                => $poItem->qty_received - $qtyRejected,
                            'balance_before'     => 0,
                            'balance_after'      => $poItem->qty_received - $qtyRejected,
                            'type'               => 'po_receive',
                            'reference_type'     => GoodsReceipt::class,
                            'reference_id'       => $gr->id,
                            'external_reference' => $grnNumber,
                            'notes'              => 'Dummy stock in',
                            'created_by'         => $user->id,
                            'created_at'         => $receiptDate,
                            'updated_at'         => $receiptDate,
                        ]);
                    }
                    $grCount++;
                }
            }
        }

        // Also create outbound stock movements (consumption) for Procurement Forecast charts
        $outTypes = ['so_delivery', 'production_out'];
        foreach ($products->take(20) as $product) {
            for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
                $monthStart = $now->copy()->subMonths($monthOffset)->startOfMonth();
                $monthEnd   = $now->copy()->subMonths($monthOffset)->endOfMonth();
                if ($monthEnd->gt($now)) $monthEnd = $now->copy();

                // 2-6 outbound movements per product per month
                $movCount = rand(2, 6);
                for ($m = 0; $m < $movCount; $m++) {
                    $movDate = $monthStart->copy()->addDays(rand(0, $monthStart->diffInDays($monthEnd)));
                    $qtyOut  = rand(5, 100);

                    StockMovement::create([
                        'product_id'         => $product->id,
                        'warehouse_id'       => $warehouse->id,
                        'qty'                => -$qtyOut,
                        'balance_before'     => $qtyOut,
                        'balance_after'      => 0,
                        'type'               => $outTypes[array_rand($outTypes)],
                        'reference_type'     => null,
                        'reference_id'       => null,
                        'external_reference' => 'DUMMY-OUT-' . rand(10000, 99999),
                        'notes'              => 'Dummy consumption',
                        'created_by'         => $user->id,
                        'created_at'         => $movDate,
                        'updated_at'         => $movDate,
                    ]);
                }
            }
        }

        $this->command->info("✅ Done! Created {$poCount} POs + {$grCount} GRs + stock movements.");
    }
}
