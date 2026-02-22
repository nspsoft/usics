<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Company;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SupplierPortalSeeder extends Seeder
{
    public function run()
    {
        if (app()->environment('production')) {
            throw new RuntimeException('SupplierPortalSeeder tidak boleh dijalankan di production.');
        }

        DB::transaction(function () {
            $this->seedData();
        });
    }

    private function seedData()
    {
        // 1. Setup Context
        $company = Company::first();
        if (!$company) {
            $company = Company::create(['name' => 'PT. SPINDO Based', 'code' => 'SPINDO']);
        }
        
        $warehouse = Warehouse::first();
        if (!$warehouse) {
            $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'company_id' => $company->id]);
        }

        // Get a supplier that has a user user attached, or create one
        $user = User::whereNotNull('supplier_id')->first();
        
        if ($user) {
            $supplier = $user->supplier;
            $this->command->info("Seeding data for Supplier: {$supplier->name} (User: {$user->email})");
        } else {
            // Create dummy supplier and user
            $seedEmail = env('SUPPLIER_PORTAL_ADMIN_EMAIL', 'admin@megasteel.co.id');
            $seedPassword = env('SUPPLIER_PORTAL_ADMIN_PASSWORD');
            if (!$seedPassword || $seedPassword === 'password') {
                throw new RuntimeException('SUPPLIER_PORTAL_ADMIN_PASSWORD wajib di-set dan tidak boleh bernilai "password".');
            }

            $supplier = Supplier::create([
                'name' => 'PT. Mega Steel Indonesia',
                'code' => 'SUP-MEGA',
                'email' => 'contact@megasteel.co.id',
                'phone' => '021-55556666',
                'address' => 'Jl. Industri Baja No. 88, Cikarang',
            ]);
            
            $user = User::factory()->create([
                'name' => 'Admin Mega Steel',
                'email' => $seedEmail,
                'password' => Hash::make($seedPassword),
                'supplier_id' => $supplier->id,
            ]);
            $this->command->info("Created new Supplier: {$supplier->name} & User: {$user->email}");
        }

        $products = Product::inRandomOrder()->take(5)->get();
        if ($products->isEmpty()) {
            $this->command->error("No products found! Please seed products first.");
            return;
        }

        // 2. Generate History (Last 6 Months)
        $months = 6;
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        for ($i = 0; $i <= $months; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $ordersCount = rand(2, 5); // 2-5 POs per month

            for ($j = 0; $j < $ordersCount; $j++) {
                $status = $this->determineStatus($currentMonth);
                $poDate = $currentMonth->copy()->addDays(rand(1, 28));
                
                $this->createOrderCycle($company, $supplier, $warehouse, $products, $poDate, $status, $user);
            }
        }
    }

    private function determineStatus($date)
    {
        $now = Carbon::now();
        $diffInWeeks = $date->diffInWeeks($now);

        if ($diffInWeeks > 4) {
            // Older than 1 month -> mostly completed
            return 'completed';
        } elseif ($diffInWeeks > 1) {
            // 1-4 weeks old -> acknowledged or delivered
            return rand(0, 1) ? 'acknowledged' : 'dispatched'; // custom status text logic
        } else {
            // Recent -> ordered or acknowledged
            return rand(0, 1) ? 'ordered' : 'acknowledged';
        }
    }

    private function createOrderCycle($company, $supplier, $warehouse, $products, $date, $cycleType, $creator)
    {
        // --- Create PO ---
        $po = new PurchaseOrder();
        $po->company_id = $company->id;
        $po->supplier_id = $supplier->id;
        $po->warehouse_id = $warehouse->id;
        $po->order_date = $date;
        $po->expected_date = $date->copy()->addDays(14);
        
        // Use logic to match typical statuses
        if ($cycleType === 'completed') {
            $po->status = PurchaseOrder::STATUS_RECEIVED; // PO Received fully
        } elseif ($cycleType === 'dispatched') {
            $po->status = PurchaseOrder::STATUS_ACKNOWLEDGED; // Still acknowledged in PO terms, but GR exists
        } elseif ($cycleType === 'acknowledged') {
            $po->status = PurchaseOrder::STATUS_ACKNOWLEDGED;
        } else {
            $po->status = PurchaseOrder::STATUS_ORDERED;
        }

        // Generate Number Manually to avoid static method complexity if needed
        $po->po_number = 'PO-' . $date->format('ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $po->currency = 'IDR';
        $po->exchange_rate = 1;
        $po->tax_percent = 11;
        $po->created_by = $creator->id;
        $po->save();

        // Add Items
        $subtotal = 0;
        $poItems = [];
        $selectedProducts = $products->random(rand(2, 4));

        foreach ($selectedProducts as $product) {
            $qty = rand(10, 100) * 10;
            $price = $product->purchase_price > 0 ? $product->purchase_price : rand(50000, 500000);
            $total = $qty * $price;
            $subtotal += $total;

            $poItem = PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $product->id,
                'description' => $product->name,
                'qty' => $qty,
                'unit_price' => $price,
                'subtotal' => $total,
                'qty_received' => ($cycleType === 'completed') ? $qty : 0, 
            ]);
            $poItems[] = $poItem;
        }

        $tax = $subtotal * 0.11;
        $po->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
        ]);

        // --- Create Goods Receipt (Delivery) ---
        if ($cycleType === 'dispatched' || $cycleType === 'completed') {
            
            $grStatus = ($cycleType === 'completed') ? GoodsReceipt::STATUS_COMPLETED : GoodsReceipt::STATUS_DISPATCHED;
            
            $gr = GoodsReceipt::create([
                'company_id' => $company->id,
                'purchase_order_id' => $po->id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier->id,
                'grn_number' => 'GRN-' . $date->format('ym') . '-' . rand(100, 999),
                'delivery_note_number' => 'SJ/' . $date->format('Y') . '/' . rand(1000, 9999),
                'receipt_date' => $date->copy()->addDays(3),
                'status' => $grStatus,
                'received_by' => $creator->id,
                'notes' => 'Generated by Seeder',
            ]);

            foreach ($poItems as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'qty_ordered' => $item->qty, // Dispatched qty
                    'qty_received' => ($grStatus === GoodsReceipt::STATUS_COMPLETED) ? $item->qty : 0,
                    'unit_cost' => $item->unit_price,
                ]);
            }
        }

        // --- Create Invoice ---
        if ($cycleType === 'completed') {
            // 80% chance of invoice being created for completed orders
            if (rand(0, 100) > 20) {
                $isPaid = rand(0, 100) > 30; // 70% paid
                $invStatus = $isPaid ? PurchaseInvoice::STATUS_PAID : PurchaseInvoice::STATUS_UNPAID;

                $invoice = PurchaseInvoice::create([
                    'company_id' => $company->id,
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $supplier->id,
                    'invoice_number' => 'INV/' . $date->format('Y') . '/' . rand(10000, 99999),
                    'invoice_date' => $date->copy()->addDays(5),
                    'due_date' => $date->copy()->addDays(35),
                    'status' => $invStatus,
                    'currency' => 'IDR',
                    'exchange_rate' => 1,
                    'subtotal' => $po->subtotal,
                    'tax_percent' => 11,
                    'tax_amount' => $po->tax_amount,
                    'total_amount' => $po->total,
                    'paid_amount' => $isPaid ? $po->total : 0,
                    'created_by' => $creator->id,
                ]);
                
                // Link items would technically be needed for strict data, but for dashboard metrics (sums), header is enough.
                // But let's add items to be safe for "Show" views.
                 foreach ($gr->items as $grItem) {
                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'goods_receipt_item_id' => $grItem->id,
                        'product_id' => $grItem->product_id,
                        'description' => $grItem->product ? $grItem->product->name : 'Item',
                        'qty' => $grItem->qty_received,
                        'unit_price' => $grItem->unit_cost,
                        'subtotal' => $grItem->qty_received * $grItem->unit_cost,
                    ]);
                }
            }
        }
    }
}
