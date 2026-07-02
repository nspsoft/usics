<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use Carbon\Carbon;

class DraftSoDoSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        $warehouse = Warehouse::where('code', 'WH-FG')->first() ?? Warehouse::first();
        $user = User::where('email', 'sales@usc-indonesia.co.id')->first() ?? User::first();
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->error('Customers or Products are empty. Seed them first.');
            return;
        }

        $this->command->info('Starting Draft SO and DO generation...');

        foreach ($customers as $index => $customer) {
            $date = Carbon::now()->subDays(rand(1, 3));
            $deliveryDate = Carbon::now()->addDays(rand(3, 7));

            // 1. Create Sales Order
            $poNumber = 'PO-' . strtoupper($customer->code) . '-' . $date->format('ymd') . '-DFT' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $soNumber = 'SO-' . $date->format('ym') . '-DFT' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $so = SalesOrder::create([
                'company_id' => $company->id,
                'so_number' => $soNumber,
                'customer_po_number' => $poNumber,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $date,
                'delivery_date' => $deliveryDate,
                'status' => 'processing', // "processing" means confirmed & ready for delivery order creation
                'currency' => 'IDR',
                'exchange_rate' => 1.000000,
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 11,
                'tax_amount' => 0,
                'total' => 0,
                'shipping_name' => $customer->name,
                'shipping_address' => $customer->address ?? 'Alamat ' . $customer->name,
                'created_by' => $user->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => $date->copy()->addHour(),
            ]);

            // 2. Add 1-2 items to SO
            $itemsToSeed = $products->random(min(rand(1, 2), $products->count()));
            $subtotal = 0;
            $soItems = [];

            foreach ($itemsToSeed as $prod) {
                $qty = rand(100, 400);
                $price = $prod->selling_price > 0 ? $prod->selling_price : rand(50000, 200000);
                $rowTotal = $qty * $price;

                $soItem = SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $prod->id,
                    'description' => $prod->name,
                    'qty' => $qty,
                    'unit_id' => $prod->unit_id ?? 1,
                    'unit_price' => $price,
                    'subtotal' => $rowTotal,
                    'qty_delivered' => 0,
                    'qty_returned' => 0,
                    'qty_invoiced' => 0,
                ]);

                $subtotal += $rowTotal;
                $soItems[] = $soItem;
            }

            $tax = $subtotal * 0.11;
            $so->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax,
            ]);

            // 3. Generate Custom DO Number
            $monthRoman = $this->getRomanMonth($deliveryDate->format('n'));
            $yearShort = $deliveryDate->format('y');
            $custCode = $customer->code ?? 'GEN';
            
            // Query total DOs in this month/year pattern to get next sequence number
            $countThisMonth = DeliveryOrder::where('do_number', 'like', "%/DO/JRI-%/{$monthRoman}/{$yearShort}")->count();
            $sequence = $countThisMonth + 1;
            $doNumber = str_pad($sequence, 3, '0', STR_PAD_LEFT) . "/DO/JRI-{$custCode}/{$monthRoman}/{$yearShort}";

            // 4. Create Delivery Order in "draft" status
            $do = DeliveryOrder::create([
                'company_id' => $company->id,
                'do_number' => $doNumber,
                'sales_order_id' => $so->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'delivery_date' => $deliveryDate,
                'status' => 'draft', // DRAFT status!
                'shipping_name' => $customer->name,
                'shipping_address' => $customer->address ?? 'Alamat ' . $customer->name,
                'prepared_by' => $user->id,
                'notes' => 'Draft Surat Jalan - Menunggu picking dan verifikasi gudang.',
            ]);

            // 5. Create DO Items
            foreach ($soItems as $soItem) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $soItem->product_id,
                    'qty_ordered' => $soItem->qty,
                    'qty_delivered' => $soItem->qty, // Target quantity to deliver
                    'unit_id' => $soItem->unit_id,
                ]);
            }
        }

        $this->command->info('Draft SO and DO data successfully seeded for all customers!');
    }

    private function getRomanMonth($month)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }
}
