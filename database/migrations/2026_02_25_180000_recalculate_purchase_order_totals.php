<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('purchase_orders')
            ->where(function ($q) {
                $q->where('total', 0)->orWhereNull('total')
                    ->orWhere('subtotal', 0)->orWhereNull('subtotal');
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('purchase_order_items')
                    ->whereColumn('purchase_order_items.purchase_order_id', 'purchase_orders.id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($now) {
                foreach ($orders as $order) {
                    $taxPercent = $order->tax_percent ?? 11;
                    $discountPercent = $order->discount_percent ?? 0;

                    $items = DB::table('purchase_order_items')
                        ->where('purchase_order_id', $order->id)
                        ->get(['id', 'qty', 'unit_price', 'discount_percent']);

                    $subtotal = 0.0;
                    foreach ($items as $item) {
                        $qty = (float) ($item->qty ?? 0);
                        $unitPrice = (float) ($item->unit_price ?? 0);
                        $itemDisc = (float) ($item->discount_percent ?? 0);

                        $gross = $qty * $unitPrice;
                        $itemDiscountAmount = $gross * ($itemDisc / 100);
                        $itemSubtotal = $gross - $itemDiscountAmount;
                        $subtotal += $itemSubtotal;

                        DB::table('purchase_order_items')
                            ->where('id', $item->id)
                            ->update([
                                'discount_amount' => $itemDiscountAmount,
                                'subtotal' => $itemSubtotal,
                                'updated_at' => $now,
                            ]);
                    }

                    $headerDiscountAmount = $subtotal * ($discountPercent / 100);
                    $afterDiscount = $subtotal - $headerDiscountAmount;
                    $taxAmount = $afterDiscount * ($taxPercent / 100);
                    $total = $afterDiscount + $taxAmount;

                    DB::table('purchase_orders')
                        ->where('id', $order->id)
                        ->update([
                            'discount_percent' => $discountPercent,
                            'tax_percent' => $taxPercent,
                            'subtotal' => $subtotal,
                            'discount_amount' => $headerDiscountAmount,
                            'tax_amount' => $taxAmount,
                            'total' => $total,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
    }
};

