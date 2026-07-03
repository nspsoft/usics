<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchasingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command->error('No company found. Please run DemoDataSeeder first.');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->command->error('No user found. Please run DemoDataSeeder first.');
            return;
        }

        $warehouse = Warehouse::where('is_default', true)->first() ?? Warehouse::first();
        if (!$warehouse) {
            $this->command->error('No warehouse found. Please run DemoDataSeeder first.');
            return;
        }

        // Fetch products relevant to PT USC that are purchased
        $products = Product::where('is_purchased', true)->get();
        if ($products->isEmpty()) {
            $this->command->error('No purchasable products found. Please run UscProductSeeder first.');
            return;
        }

        $this->command->info("Cleaning up old purchasing data...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PurchasePayment::truncate();
        PurchaseInvoiceItem::truncate();
        PurchaseInvoice::truncate();
        PurchaseReturnItem::truncate();
        PurchaseReturn::truncate();
        GoodsReceiptItem::truncate();
        GoodsReceipt::truncate();
        PurchaseOrderItem::truncate();
        PurchaseOrder::truncate();
        PurchaseRequestItem::truncate();
        PurchaseRequest::truncate();
        SupplierContact::truncate();
        // Clear stock movements that are po_receive or related to goods receipts/returns
        StockMovement::whereIn('type', ['po_receive', 'purchase_return'])
            ->orWhere('reference_type', GoodsReceipt::class)
            ->orWhere('reference_type', PurchaseReturn::class)
            ->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("Seeding realistic PT USC Suppliers...");
        $suppliersData = [
            [
                'code' => 'SUP-KS',
                'name' => 'PT Krakatau Steel (Persero) Tbk',
                'contact_person' => 'Budi Santoso',
                'email' => 'sales@krakatausteel.com',
                'phone' => '0254-371111',
                'address' => 'Jl. Industri No. 5',
                'city' => 'Cilegon',
                'state' => 'Banten',
                'postal_code' => '42435',
                'country' => 'ID',
                'payment_terms' => 'NET 60',
                'payment_days' => 60,
                'credit_limit' => 10000000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok utama Hot Rolled Coil (HRC) dan Cold Rolled Coil (CRC).',
                'contact' => [
                    'name' => 'Budi Santoso',
                    'phone' => '08111222333',
                    'email' => 'budi.santoso@krakatausteel.com',
                    'position' => 'Senior Sales Manager',
                ]
            ],
            [
                'code' => 'SUP-KP',
                'name' => 'PT Krakatau Posco',
                'contact_person' => 'Kim Jin-woo',
                'email' => 'marketing@krakatauposco.co.id',
                'phone' => '0254-848888',
                'address' => 'Kawasan Industri Krakatau, Jl. Afrika No. 2',
                'city' => 'Cilegon',
                'state' => 'Banten',
                'postal_code' => '42443',
                'country' => 'ID',
                'payment_terms' => 'NET 45',
                'payment_days' => 45,
                'credit_limit' => 15000000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok slab baja dan heavy plate berkualitas tinggi untuk slitting.',
                'contact' => [
                    'name' => 'Indra Wijaya',
                    'phone' => '08123456789',
                    'email' => 'indra.wijaya@krakatauposco.co.id',
                    'position' => 'Technical Account Representative',
                ]
            ],
            [
                'code' => 'SUP-GRP',
                'name' => 'PT Gunung Raja Paksi Tbk',
                'contact_person' => 'Hendra Wijaya',
                'email' => 'corporate@gunungrajapaksi.com',
                'phone' => '021-8900111',
                'address' => 'Jl. Perjuangan No. 8, Warung Bongkok',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'postal_code' => '17520',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 5000000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok plat baja struktural dan produk canai panas.',
                'contact' => [
                    'name' => 'Hendra Wijaya',
                    'phone' => '081822334455',
                    'email' => 'hendra.w@gunungrajapaksi.com',
                    'position' => 'Sales Director',
                ]
            ],
            [
                'code' => 'SUP-SPINDO',
                'name' => 'PT Steel Pipe Industry of Indonesia Tbk',
                'contact_person' => 'Agus Harimurti',
                'email' => 'info@spindo.co.id',
                'phone' => '031-8416281',
                'address' => 'Jl. Kalibutuh No. 189-191',
                'city' => 'Surabaya',
                'state' => 'Jawa Timur',
                'postal_code' => '60173',
                'country' => 'ID',
                'payment_terms' => 'NET 45',
                'payment_days' => 45,
                'credit_limit' => 3000000000.00,
                'currency' => 'IDR',
                'notes' => 'Mitra strategis pemasok bahan pipa dan tabung baja.',
                'contact' => [
                    'name' => 'Agus Harimurti',
                    'phone' => '0811345678',
                    'email' => 'agus.h@spindo.co.id',
                    'position' => 'Key Account Manager',
                ]
            ],
            [
                'code' => 'SUP-ESSAR',
                'name' => 'PT Essar Indonesia',
                'contact_person' => 'Ravi Kumar',
                'email' => 'sales@essarindonesia.co.id',
                'phone' => '021-8980150',
                'address' => 'Kawasan Industri MM2100 Blok B1-2',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'postal_code' => '17520',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 4000000000.00,
                'currency' => 'IDR',
                'notes' => 'Spesialis pemasok Cold Rolled Coil (CRC) dan Galvanized Steel.',
                'contact' => [
                    'name' => 'Sanjay Gupta',
                    'phone' => '08129990001',
                    'email' => 'sanjay.gupta@essarindonesia.co.id',
                    'position' => 'Commercial Lead',
                ]
            ],
            [
                'code' => 'SUP-NS',
                'name' => 'Nippon Steel Corporation',
                'contact_person' => 'Takashi Yamazaki',
                'email' => 'yamazaki.t@nipponsteel.jp',
                'phone' => '021-2525656',
                'address' => 'Summitmas II Lt. 15, Jl. Jend. Sudirman Kav. 61-62',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'postal_code' => '12190',
                'country' => 'ID',
                'payment_terms' => 'NET 90',
                'payment_days' => 90,
                'credit_limit' => 25000000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok koil baja berkualitas otomotif (special grade) langsung dari Jepang.',
                'contact' => [
                    'name' => 'Kenji Sato',
                    'phone' => '08138887776',
                    'email' => 'sato.k@nipponsteel.jp',
                    'position' => 'Liaison Officer',
                ]
            ],
            [
                'code' => 'SUP-KANEFUSA',
                'name' => 'PT Kanefusa Indonesia',
                'contact_person' => 'Yusuf Mansur',
                'email' => 'sales@kanefusa.co.id',
                'phone' => '021-8983300',
                'address' => 'Kawasan Industri EJIP Plot 8D, Cikarang Selatan',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'postal_code' => '17550',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 500000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok pisau mesin slitting rotary knife dan shearing upper blade.',
                'contact' => [
                    'name' => 'Yusuf Mansur',
                    'phone' => '08785554443',
                    'email' => 'yusuf@kanefusa.co.id',
                    'position' => 'Technical Sales Engineer',
                ]
            ],
            [
                'code' => 'SUP-SAMATOR',
                'name' => 'PT Samator Indo Gas Tbk',
                'contact_person' => 'Denny Wijaya',
                'email' => 'customercare@samator.com',
                'phone' => '021-83709111',
                'address' => 'Jl. Dr. Saharjo No. 83',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'postal_code' => '12970',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 200000000.00,
                'currency' => 'IDR',
                'notes' => 'Pemasok gas industri (Oksigen, Nitrogen, Argon) untuk laser cutting/processing.',
                'contact' => [
                    'name' => 'Denny Wijaya',
                    'phone' => '081512345678',
                    'email' => 'denny.wijaya@samator.com',
                    'position' => 'Branch Account Representative',
                ]
            ],
            [
                'code' => 'SUP-IRONBIRD',
                'name' => 'PT Iron Bird Logistics',
                'contact_person' => 'Aditya Siregar',
                'email' => 'info@ironbird.co.id',
                'phone' => '021-7989333',
                'address' => 'Jl. Mampang Prapatan No. 60',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'postal_code' => '12790',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 1000000000.00,
                'currency' => 'IDR',
                'notes' => 'Mitra penyedia transportasi truk trailer khusus coil berat.',
                'contact' => [
                    'name' => 'Aditya Siregar',
                    'phone' => '08126667778',
                    'email' => 'aditya.s@ironbird.co.id',
                    'position' => 'Operations Manager',
                ]
            ],
            [
                'code' => 'SUP-PUNINAR',
                'name' => 'PT Puninar Jaya',
                'contact_person' => 'Rian Kurniawan',
                'email' => 'sales@puninar.com',
                'phone' => '021-4602277',
                'address' => 'Jl. Raya Cakung Cilincing KM 1.5',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'postal_code' => '13910',
                'country' => 'ID',
                'payment_terms' => 'NET 30',
                'payment_days' => 30,
                'credit_limit' => 1000000000.00,
                'currency' => 'IDR',
                'notes' => 'Penyedia jasa logistik pergudangan dan transportasi raw material.',
                'contact' => [
                    'name' => 'Rian Kurniawan',
                    'phone' => '0811999888',
                    'email' => 'rian.k@puninar.com',
                    'position' => 'Logistics Consultant',
                ]
            ],
        ];

        foreach ($suppliersData as $sup) {
            $contact = $sup['contact'];
            unset($sup['contact']);

            $supplierModel = Supplier::updateOrCreate(
                ['code' => $sup['code']],
                array_merge($sup, ['company_id' => $company->id, 'is_active' => true])
            );

            // Seed Supplier Contact
            SupplierContact::create(array_merge($contact, [
                'supplier_id' => $supplierModel->id,
            ]));
        }

        $supplierModels = Supplier::all();

        // 3. Create Purchase Requests (PRs)
        $this->command->info("Seeding Purchase Requests...");
        $prDepartments = ['Production', 'Maintenance', 'Warehouse', 'QA'];
        $prStatuses = ['draft', 'submitted', 'approved', 'rejected'];

        for ($i = 0; $i < 20; $i++) {
            $date = Carbon::now()->subDays(rand(1, 180));
            $isDraft = $i < 3;
            $status = $isDraft ? 'draft' : $prStatuses[array_rand($prStatuses)];

            $pr = PurchaseRequest::create([
                'company_id' => $company->id,
                'pr_number' => 'PR-' . $date->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'department' => $prDepartments[array_rand($prDepartments)],
                'requester' => $user->name,
                'request_date' => $date,
                'status' => $status,
                'notes' => 'Pengajuan pembelian bahan baku/alat untuk operasional produksi.',
                'created_by' => $user->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add 1-3 items to PR
            $prItemCount = rand(1, 3);
            $prProducts = $products->random(min($prItemCount, $products->count()));
            foreach ($prProducts as $prod) {
                $qty = $prod->product_type === 'raw_material' ? rand(5000, 25000) : rand(2, 10);
                $pr->items()->create([
                    'product_id' => $prod->id,
                    'qty' => $qty,
                    'description' => 'Permintaan stock ' . $prod->name,
                ]);
            }
        }

        // 4. Create Purchase Orders (POs), Goods Receipts (GRs), Invoices, Payments, Returns
        $this->command->info("Seeding Purchase Orders, Goods Receipts, Invoices, Payments, Returns...");

        $poStatuses = [
            'draft', 'submitted', 'approved', 'ordered', 'partial', 'received', 'cancelled'
        ];

        // We will seed around 120 POs distributed across the last 6 months
        for ($i = 0; $i < 120; $i++) {
            $date = Carbon::now()->subDays(rand(1, 180));
            $supplier = $supplierModels->random();
            
            // Determine status based on age: older orders should be received or cancelled
            if ($date->lt(Carbon::now()->subMonths(1))) {
                $status = rand(0, 10) > 1 ? 'received' : 'cancelled';
            } else {
                $status = $poStatuses[array_rand($poStatuses)];
            }

            // Map supplier to relevant products to keep it realistic
            if (in_array($supplier->code, ['SUP-KS', 'SUP-KP', 'SUP-GRP', 'SUP-SPINDO', 'SUP-ESSAR', 'SUP-NS'])) {
                // Steel Mills supply coils
                $poProducts = $products->filter(function ($p) {
                    return in_array($p->sku, ['COIL-HR-SPHC-2.0', 'COIL-CR-SPCC-1.2', 'COIL-GA-SGCC-0.8', 'SC-HRC-TRIAL']);
                });
            } else {
                // Tooling & Gas suppliers supply spare parts
                $poProducts = $products->filter(function ($p) {
                    return in_array($p->sku, ['SP-SLITKNIFE-SKD11-300', 'SP-SHEARBLADE-2500', 'SP-RUBBERING-120']);
                });
            }

            if ($poProducts->isEmpty()) {
                $poProducts = $products;
            }

            // Create PO
            $poNumber = 'PO-' . $date->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $po = PurchaseOrder::create([
                'company_id' => $company->id,
                'po_number' => $poNumber,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $date,
                'expected_date' => $date->copy()->addDays(rand(5, 14)),
                'status' => $status,
                'currency' => 'IDR',
                'exchange_rate' => 1.0,
                'subtotal' => 0,
                'discount_percent' => rand(0, 5) === 0 ? rand(1, 5) : 0.0, // 20% chance of small discount
                'discount_amount' => 0,
                'tax_percent' => 11.0,
                'tax_amount' => 0,
                'total' => 0,
                'notes' => 'Generated by PurchasingSeeder',
                'created_by' => $user->id,
                'approved_by' => in_array($status, ['approved', 'ordered', 'partial', 'received']) ? $user->id : null,
                'approved_at' => in_array($status, ['approved', 'ordered', 'partial', 'received']) ? $date->copy()->addDay() : null,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add 1-2 items to PO
            $selectedProds = $poProducts->random(min(rand(1, 2), $poProducts->count()));
            $subtotal = 0;
            $poItems = [];

            foreach ($selectedProds as $prod) {
                // Large quantity in kg for coils, small count in pcs for spare parts
                $qty = $prod->product_type === 'raw_material' ? rand(10000, 40000) : rand(5, 50);
                $price = $prod->cost_price > 0 ? $prod->cost_price : rand(50000, 500000);
                $lineTotal = $qty * $price;

                $qtyReceived = 0;
                if ($status === 'received') {
                    $qtyReceived = $qty;
                } elseif ($status === 'partial') {
                    $qtyReceived = rand(floor($qty * 0.3), floor($qty * 0.8));
                }

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $prod->id,
                    'description' => $prod->name,
                    'qty' => $qty,
                    'unit_id' => $prod->unit_id,
                    'unit_price' => $price,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'subtotal' => $lineTotal,
                    'qty_received' => $qtyReceived,
                    'qty_returned' => 0,
                ]);

                $subtotal += $lineTotal;
                $poItems[] = $poItem;
            }

            // Calculate totals
            $discountAmt = $subtotal * ($po->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmt;
            $taxAmt = $afterDiscount * 0.11;
            $totalAmount = $afterDiscount + $taxAmt;

            $po->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_amount' => $taxAmt,
                'total' => $totalAmount,
            ]);

            // 5. Generate Goods Receipt (GR)
            if (in_array($status, ['received', 'partial'])) {
                $receiptDate = $po->expected_date->copy()->addDays(rand(-3, 5));
                if ($receiptDate->gt(Carbon::now())) {
                    $receiptDate = Carbon::now();
                }

                $grnNumber = 'GRN-' . $receiptDate->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                $gr = GoodsReceipt::create([
                    'company_id' => $company->id,
                    'grn_number' => $grnNumber,
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'receipt_date' => $receiptDate,
                    'delivery_note_number' => 'DN/' . $receiptDate->format('Y') . '/' . rand(10000, 99999),
                    'status' => $status === 'received' ? 'completed' : 'received', // Completed if fully received
                    'notes' => 'Penerimaan barang dari PO ' . $poNumber,
                    'received_by' => $user->id,
                    'created_at' => $receiptDate,
                    'updated_at' => $receiptDate,
                ]);

                $grItems = [];
                $hasRejected = false;
                $rejectedData = [];

                foreach ($poItems as $poItem) {
                    if ($poItem->qty_received <= 0) continue;

                    // 8% chance of small QC rejection
                    $qtyRejected = (rand(1, 100) <= 8) ? rand(1, max(1, floor($poItem->qty_received * 0.05))) : 0;
                    if ($qtyRejected > 0) {
                        $hasRejected = true;
                        $rejectedData[] = [
                            'po_item' => $poItem,
                            'qty_rejected' => $qtyRejected,
                        ];
                    }

                    $grItem = GoodsReceiptItem::create([
                        'goods_receipt_id' => $gr->id,
                        'purchase_order_item_id' => $poItem->id,
                        'product_id' => $poItem->product_id,
                        'qty_ordered' => $poItem->qty,
                        'qty_received' => $poItem->qty_received,
                        'qty_rejected' => $qtyRejected,
                        'unit_id' => $poItem->unit_id,
                        'unit_cost' => $poItem->unit_price,
                        'notes' => $qtyRejected > 0 ? "Gagal QC sebanyak {$qtyRejected}" : null,
                    ]);

                    $grItems[] = $grItem;

                    // Create stock movement for received parts (excluding rejected parts)
                    $acceptedQty = $poItem->qty_received - $qtyRejected;
                    if ($acceptedQty > 0) {
                        StockMovement::create([
                            'product_id' => $poItem->product_id,
                            'warehouse_id' => $warehouse->id,
                            'qty' => $acceptedQty,
                            'balance_before' => 0, // Simplified
                            'balance_after' => $acceptedQty,
                            'type' => 'po_receive',
                            'reference_type' => GoodsReceipt::class,
                            'reference_id' => $gr->id,
                            'external_reference' => $grnNumber,
                            'notes' => 'Penerimaan barang masuk dari PO ' . $poNumber,
                            'created_by' => $user->id,
                            'created_at' => $receiptDate,
                            'updated_at' => $receiptDate,
                        ]);
                    }
                }

                // 6. Generate Purchase Invoice & Payments (for received POs)
                // 85% chance of generating invoice
                if (rand(1, 100) <= 85) {
                    $invoiceDate = $receiptDate->copy()->addDays(rand(2, 7));
                    $dueDate = $invoiceDate->copy()->addDays($supplier->payment_days ?? 30);
                    $invoiceNumber = 'PINV-' . $invoiceDate->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

                    // Determine invoice payment status based on due date
                    $isPastDueDate = $dueDate->lt(Carbon::now());
                    if ($isPastDueDate) {
                        $invoiceStatus = rand(1, 10) > 1 ? PurchaseInvoice::STATUS_PAID : PurchaseInvoice::STATUS_PARTIAL;
                    } else {
                        // Mix of Paid, Partial, and Unpaid for recent invoices
                        $invRand = rand(1, 3);
                        $invoiceStatus = ($invRand === 1) ? PurchaseInvoice::STATUS_PAID : (($invRand === 2) ? PurchaseInvoice::STATUS_PARTIAL : PurchaseInvoice::STATUS_UNPAID);
                    }

                    // Create Invoice
                    $invoice = PurchaseInvoice::create([
                        'company_id' => $company->id,
                        'invoice_number' => $invoiceNumber,
                        'purchase_order_id' => $po->id,
                        'supplier_id' => $supplier->id,
                        'invoice_date' => $invoiceDate,
                        'due_date' => $dueDate,
                        'status' => $invoiceStatus,
                        'currency' => 'IDR',
                        'exchange_rate' => 1.0,
                        'subtotal' => $po->subtotal,
                        'tax_percent' => $po->tax_percent,
                        'tax_amount' => $po->tax_amount,
                        'discount_total' => $po->discount_amount,
                        'total_amount' => $po->total,
                        'paid_amount' => 0.0,
                        'notes' => 'Faktur Tagihan Supplier untuk PO ' . $poNumber,
                        'created_by' => $user->id,
                        'created_at' => $invoiceDate,
                        'updated_at' => $invoiceDate,
                    ]);

                    // Seed Invoice Items
                    foreach ($grItems as $grItem) {
                        PurchaseInvoiceItem::create([
                            'purchase_invoice_id' => $invoice->id,
                            'goods_receipt_item_id' => $grItem->id,
                            'product_id' => $grItem->product_id,
                            'description' => $grItem->product ? $grItem->product->name : 'Baja / Spare Part',
                            'qty' => $grItem->qty_received,
                            'unit_price' => $grItem->unit_cost,
                            'subtotal' => $grItem->qty_received * $grItem->unit_cost,
                        ]);
                    }

                    // Create payments if Paid or Partial
                    if ($invoiceStatus === PurchaseInvoice::STATUS_PAID) {
                        $payDate = $invoiceDate->copy()->addDays(rand(1, 15));
                        if ($payDate->gt(Carbon::now())) $payDate = Carbon::now();

                        PurchasePayment::create([
                            'purchase_invoice_id' => $invoice->id,
                            'payment_number' => 'PAY-' . $payDate->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                            'amount' => $invoice->total_amount,
                            'payment_date' => $payDate,
                            'payment_method' => PurchasePayment::METHOD_TRANSFER,
                            'reference' => 'TRF/' . rand(1000000, 9999999),
                            'bank_name' => 'Bank Mandiri',
                            'account_number' => '1370008888' . rand(10, 99),
                            'notes' => 'Pelunasan tagihan faktur ' . $invoiceNumber,
                            'created_by' => $user->id,
                            'created_at' => $payDate,
                            'updated_at' => $payDate,
                        ]);

                        $invoice->update(['paid_amount' => $invoice->total_amount]);
                    } elseif ($invoiceStatus === PurchaseInvoice::STATUS_PARTIAL) {
                        $payDate = $invoiceDate->copy()->addDays(rand(1, 10));
                        if ($payDate->gt(Carbon::now())) $payDate = Carbon::now();
                        
                        $paidAmt = $invoice->total_amount * (rand(30, 60) / 100);

                        PurchasePayment::create([
                            'purchase_invoice_id' => $invoice->id,
                            'payment_number' => 'PAY-' . $payDate->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                            'amount' => $paidAmt,
                            'payment_date' => $payDate,
                            'payment_method' => PurchasePayment::METHOD_TRANSFER,
                            'reference' => 'TRF/' . rand(1000000, 9999999),
                            'bank_name' => 'Bank Mandiri',
                            'account_number' => '1370008888' . rand(10, 99),
                            'notes' => 'Pembayaran sebagian tagihan faktur ' . $invoiceNumber,
                            'created_by' => $user->id,
                            'created_at' => $payDate,
                            'updated_at' => $payDate,
                        ]);

                        $invoice->update(['paid_amount' => $paidAmt]);
                    }
                }

                // 7. Seed Purchase Returns (if there were rejected items)
                if ($hasRejected) {
                    $returnDate = $receiptDate->copy()->addDays(rand(1, 3));
                    if ($returnDate->gt(Carbon::now())) $returnDate = Carbon::now();

                    $retNumber = 'RET-' . $returnDate->format('Ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                    $totalReturnAmt = 0;

                    $ret = PurchaseReturn::create([
                        'number' => $retNumber,
                        'purchase_order_id' => $po->id,
                        'supplier_id' => $supplier->id,
                        'warehouse_id' => $warehouse->id,
                        'return_date' => $returnDate,
                        'reason' => 'Rusak saat pengiriman / Gagal tes QC standar.',
                        'status' => 'completed',
                        'total_amount' => 0.0,
                        'created_by' => $user->id,
                        'created_at' => $returnDate,
                        'updated_at' => $returnDate,
                    ]);

                    foreach ($rejectedData as $rej) {
                        $poItem = $rej['po_item'];
                        $qtyRej = $rej['qty_rejected'];
                        $linePrice = $qtyRej * $poItem->unit_price;
                        $totalReturnAmt += $linePrice;

                        PurchaseReturnItem::create([
                            'purchase_return_id' => $ret->id,
                            'product_id' => $poItem->product_id,
                            'qty' => $qtyRej,
                            'unit_price' => $poItem->unit_price,
                            'total_price' => $linePrice,
                        ]);

                        // Update PO Item qty_returned
                        $poItem->update(['qty_returned' => $qtyRej]);

                        // Create StockMovement to deduct stock
                        StockMovement::create([
                            'product_id' => $poItem->product_id,
                            'warehouse_id' => $warehouse->id,
                            'qty' => -$qtyRej,
                            'balance_before' => 0,
                            'balance_after' => -$qtyRej,
                            'type' => 'purchase_return',
                            'reference_type' => PurchaseReturn::class,
                            'reference_id' => $ret->id,
                            'external_reference' => $retNumber,
                            'notes' => 'Pengurangan stok akibat retur QC gagal ke supplier',
                            'created_by' => $user->id,
                            'created_at' => $returnDate,
                            'updated_at' => $returnDate,
                        ]);
                    }

                    $ret->update(['total_amount' => $totalReturnAmt]);
                }
            }
        }

        $this->command->info("Purchasing dummy data seeded successfully!");
    }
}
