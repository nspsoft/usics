<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DeliverySchedule;
use App\Models\SalesForecast;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\CRM\SalesVisit;
use App\Models\ApprovalRequest;
use App\Models\ApprovalHistory;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch baseline data
        $company = Company::first();
        if (!$company) {
            $company = Company::create([
                'code' => 'USC-ID',
                'name' => 'PT United Steel Center Indonesia',
                'legal_name' => 'PT United Steel Center Indonesia',
                'address' => 'Kawasan Industri KIIC Lot C-4, Jl. Tol Jakarta-Cikampek KM 47',
                'city' => 'Karawang',
                'state' => 'Jawa Barat',
                'postal_code' => '41361',
                'country' => 'ID',
                'phone' => '0267-640300',
                'email' => 'info@usc-indonesia.co.id',
                'tax_id' => '01.002.888.8-092.000',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]);
        }

        $user = User::where('email', 'admin@usc-indonesia.co.id')->first() ?? User::first();
        if (!$user) {
            $this->command->error('No users found in database. Please run RoleSeeder first.');
            return;
        }

        $warehouse = Warehouse::where('name', 'LIKE', '%Finished%')->first() 
            ?? Warehouse::where('is_default', true)->first() 
            ?? Warehouse::first();
        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'code' => 'WH-FG',
                'name' => 'Finished Goods Warehouse',
                'type' => 'warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // 2. Delete Sales & related tables to avoid duplicate keys / constraints issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SalesReturnItem::query()->delete();
        SalesReturn::query()->delete();
        SalesPayment::query()->delete();
        SalesInvoiceItem::query()->delete();
        SalesInvoice::query()->delete();
        DeliveryOrderItem::query()->delete();
        DeliveryOrder::query()->delete();
        DeliverySchedule::query()->delete();
        SalesForecast::query()->delete();
        SalesOrderItem::query()->delete();
        SalesOrder::query()->delete();
        QuotationItem::query()->delete();
        Quotation::query()->delete();
        DB::table('coa_documents')->delete();
        SalesVisit::query()->delete();
        CustomerContact::query()->delete();
        Customer::query()->delete();

        // Clear approval request records for sales docs
        DB::table('approval_histories')->whereIn('approval_request_id', function ($query) {
            $query->select('id')->from('approval_requests')->whereIn('document_type', ['Quotation', 'SalesOrder']);
        })->delete();
        DB::table('approval_requests')->whereIn('document_type', ['Quotation', 'SalesOrder'])->delete();

        // Nullify sales_order_id in work_orders to prevent orphan constraints (if they exist)
        if (Schema::hasTable('work_orders')) {
            DB::table('work_orders')->update(['sales_order_id' => null]);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Sales transaction tables and customer tables truncated successfully.');

        // 3. Seed Realistic Customers (PT USC Relevant)
        $customersData = [
            [
                'code' => 'CUST-TMMIN',
                'name' => 'PT Toyota Motor Manufacturing Indonesia',
                'contact_person' => 'Budi Santoso',
                'address' => 'Kawasan Industri KIIC Lot DD 1, Jl. Tol Jakarta-Cikampek KM 47',
                'city' => 'Karawang',
                'state' => 'Jawa Barat',
                'postal_code' => '41361',
                'country' => 'Indonesia',
                'phone' => '0267-640123',
                'email' => 'procurement.tmmin@toyota.co.id',
                'website' => 'www.toyotaindonesiamanufacturing.co.id',
                'tax_id' => '01.002.345.6-092.000',
                'bank_name' => 'Bank Mandiri',
                'account_number' => '1020009876543',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 5000000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive',
            ],
            [
                'code' => 'CUST-ADM',
                'name' => 'PT Astra Daihatsu Motor',
                'contact_person' => 'Hendra Wijaya',
                'address' => 'Jl. Gaya Motor III No. 5, Sunter II',
                'city' => 'Jakarta Utara',
                'state' => 'DKI Jakarta',
                'postal_code' => '14330',
                'country' => 'Indonesia',
                'phone' => '021-6510345',
                'email' => 'purchasing@daihatsu.astra.co.id',
                'website' => 'www.daihatsu.co.id',
                'tax_id' => '01.003.567.8-012.000',
                'bank_name' => 'Permata Bank',
                'account_number' => '4101234567',
                'payment_terms' => 'Net 45',
                'payment_days' => 45,
                'credit_limit' => 4000000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive',
            ],
            [
                'code' => 'CUST-HPM',
                'name' => 'PT Honda Prospect Motor',
                'contact_person' => 'Agus Setiawan',
                'address' => 'Kawasan Industri Mitrakarawang, Jl. Mitra Utara II, Desa Parungmulya',
                'city' => 'Karawang',
                'state' => 'Jawa Barat',
                'postal_code' => '41361',
                'country' => 'Indonesia',
                'phone' => '0267-440777',
                'email' => 'purchasing@hpm.co.id',
                'website' => 'www.honda-indonesia.com',
                'tax_id' => '01.001.234.5-092.000',
                'bank_name' => 'Bank Central Asia',
                'account_number' => '0353112233',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 3000000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive',
            ],
            [
                'code' => 'CUST-SIM',
                'name' => 'PT Suzuki Indomobil Motor',
                'contact_person' => 'Rian Hidayat',
                'address' => 'Kawasan Industri Greendland Lot II No. 1, Deltamas',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'postal_code' => '17530',
                'country' => 'Indonesia',
                'phone' => '021-89912345',
                'email' => 'procurement@suzuki.co.id',
                'website' => 'www.suzuki.co.id',
                'tax_id' => '01.004.789.0-015.000',
                'bank_name' => 'Bank Negara Indonesia',
                'account_number' => '1122334455',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 3500000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive',
            ],
            [
                'code' => 'CUST-MMKI',
                'name' => 'PT Mitsubishi Motors Krama Yudha Indonesia',
                'contact_person' => 'Denny Prabowo',
                'address' => 'Kawasan Industri GIIC Blok AA No. 1, Deltamas',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'postal_code' => '17530',
                'country' => 'Indonesia',
                'phone' => '021-89923000',
                'email' => 'purchasing@mitsubishi-motors.co.id',
                'website' => 'www.mitsubishi-motors.co.id',
                'tax_id' => '01.005.123.4-015.000',
                'bank_name' => 'Bank Danamon',
                'account_number' => '9876543210',
                'payment_terms' => 'Net 45',
                'payment_days' => 45,
                'credit_limit' => 4500000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive',
            ],
            [
                'code' => 'CUST-AOP',
                'name' => 'PT Astra Otoparts Tbk',
                'contact_person' => 'Yusuf Mansur',
                'address' => 'Jl. Raya Pegangsaan Dua Km. 2,2, Kelapa Gading',
                'city' => 'Jakarta Utara',
                'state' => 'DKI Jakarta',
                'postal_code' => '14250',
                'country' => 'Indonesia',
                'phone' => '021-4603550',
                'email' => 'purchasing@aop.astra.co.id',
                'website' => 'www.astra-otoparts.com',
                'tax_id' => '01.006.543.2-012.000',
                'bank_name' => 'Bank Central Asia',
                'account_number' => '0358899001',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 2000000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Automotive Component',
            ],
            [
                'code' => 'CUST-PMI',
                'name' => 'PT Panasonic Manufacturing Indonesia',
                'contact_person' => 'Ferry Santosa',
                'address' => 'Jl. Raya Bogor Km. 29, Gandaria, Pekayon',
                'city' => 'Jakarta Timur',
                'state' => 'DKI Jakarta',
                'postal_code' => '13710',
                'country' => 'Indonesia',
                'phone' => '021-8710221',
                'email' => 'purchasing.pmi@id.panasonic.com',
                'website' => 'www.panasonic.com/id',
                'tax_id' => '01.002.555.5-018.000',
                'bank_name' => 'Bank Mandiri',
                'account_number' => '1020001112223',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 1500000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Electronics',
            ],
            [
                'code' => 'CUST-SEID',
                'name' => 'PT Sharp Electronics Indonesia',
                'contact_person' => 'Adi Wijaya',
                'address' => 'Kawasan Industri KIIC Lot F 3, Jl. Tol Jakarta-Cikampek KM 47',
                'city' => 'Karawang',
                'state' => 'Jawa Barat',
                'postal_code' => '41361',
                'country' => 'Indonesia',
                'phone' => '0267-641234',
                'email' => 'purchasing@sharp-indonesia.co.id',
                'website' => 'www.id.sharp',
                'tax_id' => '01.003.888.8-092.000',
                'bank_name' => 'Bank Mandiri',
                'account_number' => '1020004445556',
                'payment_terms' => 'Net 45',
                'payment_days' => 45,
                'credit_limit' => 1800000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Electronics',
            ],
            [
                'code' => 'CUST-SPIN',
                'name' => 'PT Steel Pipe Industry of Indonesia Tbk',
                'contact_person' => 'Eko Prasetyo',
                'address' => 'Jl. Kalibutuh No. 189-191',
                'city' => 'Surabaya',
                'state' => 'Jawa Timur',
                'postal_code' => '60173',
                'country' => 'Indonesia',
                'phone' => '031-5313111',
                'email' => 'sales@spindo.co.id',
                'website' => 'www.spindo.com',
                'tax_id' => '01.001.999.9-606.000',
                'bank_name' => 'Bank Central Asia',
                'account_number' => '0359998887',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'credit_limit' => 2500000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Steel Fabrication',
            ],
            [
                'code' => 'CUST-WIKA',
                'name' => 'PT Waskita Karya Tbk',
                'contact_person' => 'Taufik Hidayat',
                'address' => 'Jl. M.T. Haryono Kav. No. 10',
                'city' => 'Jakarta Timur',
                'state' => 'DKI Jakarta',
                'postal_code' => '13340',
                'country' => 'Indonesia',
                'phone' => '021-8508510',
                'email' => 'procurement@waskita.co.id',
                'website' => 'www.waskita.co.id',
                'tax_id' => '01.002.777.7-018.000',
                'bank_name' => 'Bank Rakyat Indonesia',
                'account_number' => '020601000888301',
                'payment_terms' => 'Net 60',
                'payment_days' => 60,
                'credit_limit' => 3000000000.00,
                'currency' => 'IDR',
                'customer_type' => 'Construction',
            ]
        ];

        foreach ($customersData as $cData) {
            $cData['country'] = 'ID';
            $customer = Customer::create(array_merge($cData, [
                'company_id' => $company->id,
                'is_active' => true,
            ]));

            // Customer Contact
            CustomerContact::create([
                'customer_id' => $customer->id,
                'name' => $customer->contact_person,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'position' => 'Procurement Manager',
            ]);

            CustomerContact::create([
                'customer_id' => $customer->id,
                'name' => 'Finance Dept',
                'phone' => $customer->phone,
                'email' => str_replace('procurement', 'finance', str_replace('purchasing', 'finance', $customer->email)),
                'position' => 'Finance Executive',
            ]);
        }

        $customers = Customer::all();
        $this->command->info(count($customers) . ' USC-relevant customers and contacts seeded successfully.');

        // 4. Products fetching (seeded by UscProductSeeder)
        $products = Product::where('is_sold', true)->get();
        if ($products->isEmpty()) {
            $this->command->error('No products found for sales. Running UscProductSeeder first...');
            $this->call(UscProductSeeder::class);
            $products = Product::where('is_sold', true)->get();
        }

        $productsBySku = $products->keyBy('sku');

        // Customer product preferences map
        $custProductMap = [
            'CUST-TMMIN' => ['FG-BLNK-FENDER-CR12', 'FG-BLNK-DISCBRK-HR26', 'FG-TWB-PILLAR-03'],
            'CUST-ADM' => ['FG-BLNK-HOODIN-GA08', 'FG-BLNK-ROOF-CR16'],
            'CUST-HPM' => ['FG-TWB-DOORPANEL-01', 'FG-TWB-SIDEPANEL-02'],
            'CUST-SIM' => ['FG-BLNK-FENDER-CR12', 'FG-BLNK-HOODIN-GA08'],
            'CUST-MMKI' => ['FG-BLNK-FENDER-CR12', 'FG-TWB-PILLAR-03'],
            'CUST-AOP' => ['FG-BLNK-DISCBRK-HR26'],
            'CUST-PMI' => ['FG-COMP-ACBRACKET-2.0'],
            'CUST-SEID' => ['FG-COMP-ACBRACKET-2.0'],
            'CUST-SPIN' => ['SLIT-HR-SPHC-2.0-150', 'SLIT-CR-SPCC-1.2-120'],
            'CUST-WIKA' => ['SHT-HR-SPHC-2.0-1219x2438', 'SHT-CR-SPCC-1.2-1219x2438']
        ];

        $getCustomerProducts = function ($customerCode) use ($productsBySku, $products, $custProductMap) {
            $skus = $custProductMap[$customerCode] ?? [];
            $mapped = collect();
            foreach ($skus as $sku) {
                if (isset($productsBySku[$sku])) {
                    $mapped->push($productsBySku[$sku]);
                }
            }
            if ($mapped->isEmpty()) {
                return $products->random(min(2, $products->count()));
            }
            return $mapped;
        };

        // 5. Seed Approval Workflows if empty
        $salesManagerRoleId = Role::where('name', 'Sales Manager')->value('id');
        $superAdminRoleId = Role::where('name', 'Super Admin')->value('id');
        $approverId = $salesManagerRoleId ?? $superAdminRoleId ?? 1;

        $workflowQuo = Workflow::firstOrCreate(
            ['document_type' => 'Quotation', 'is_active' => true],
            [
                'name' => 'Default Sales Quotation Approval',
                'description' => 'Standard workflow for Quotations approval',
                'is_auto_approve' => false,
                'priority' => 1,
            ]
        );

        WorkflowStep::firstOrCreate(
            ['workflow_id' => $workflowQuo->id, 'step_order' => 1],
            [
                'approver_type' => 'role',
                'approver_id' => $approverId,
                'can_skip' => false,
                'timeout_days' => 3,
            ]
        );

        $workflowSO = Workflow::firstOrCreate(
            ['document_type' => 'SalesOrder', 'is_active' => true],
            [
                'name' => 'Default Sales Order Approval',
                'description' => 'Standard workflow for Sales Orders approval',
                'is_auto_approve' => false,
                'priority' => 1,
            ]
        );

        WorkflowStep::firstOrCreate(
            ['workflow_id' => $workflowSO->id, 'step_order' => 1],
            [
                'approver_type' => 'role',
                'approver_id' => $approverId,
                'can_skip' => false,
                'timeout_days' => 3,
            ]
        );

        // 6. Generate Sales Forecasts (Last 6 months + Current + Next)
        $periods = [];
        for ($m = -6; $m <= 1; $m++) {
            $periods[] = Carbon::now()->addMonths($m)->startOfMonth();
        }

        foreach ($customers as $customer) {
            $custProducts = $getCustomerProducts($customer->code);
            foreach ($periods as $period) {
                foreach ($custProducts as $product) {
                    SalesForecast::create([
                        'customer_id' => $customer->id,
                        'product_id' => $product->id,
                        'period' => $period,
                        'qty_forecast' => rand(150, 800),
                        'notes' => 'Monthly forecast for ' . $customer->code . ' / ' . $product->sku,
                    ]);
                }
            }
        }
        $this->command->info('Sales Forecasts seeded.');

        // 7. Seed Quotations
        $quotationCount = 15;
        $statuses = ['draft', 'sent', 'accepted', 'rejected'];
        $quoApprovalStatuses = [
            'draft' => 'pending',
            'sent' => 'pending',
            'accepted' => 'approved',
            'rejected' => 'rejected'
        ];

        for ($i = 0; $i < $quotationCount; $i++) {
            $date = Carbon::now()->subDays(rand(10, 120));
            $customer = $customers->random();
            $custProducts = $getCustomerProducts($customer->code);
            $status = $statuses[array_rand($statuses)];

            $quo = Quotation::create([
                'number' => Quotation::generateNumber($customer->id, $date->format('Y-m-d')),
                'customer_id' => $customer->id,
                'quotation_date' => $date,
                'valid_until' => $date->copy()->addDays(30),
                'status' => $status,
                'approval_status' => $quoApprovalStatuses[$status],
                'notes' => 'Quotation generated by SalesSeeder.',
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
                'created_by' => $user->id,
            ]);

            $subtotal = 0;
            $itemsToSeed = $custProducts->random(min(rand(1, 2), $custProducts->count()));
            foreach ($itemsToSeed as $prod) {
                $qty = rand(100, 500);
                $price = $prod->selling_price > 0 ? $prod->selling_price : rand(50000, 250000);
                $rowTotal = $qty * $price;

                QuotationItem::create([
                    'quotation_id' => $quo->id,
                    'product_id' => $prod->id,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'total_price' => $rowTotal,
                ]);
                $subtotal += $rowTotal;
            }

            $tax = $subtotal * 0.11;
            $quo->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ]);

            // Create Approval Request and history for Quotation if it's not draft/sent
            if (in_array($status, ['accepted', 'rejected'])) {
                $req = ApprovalRequest::create([
                    'workflow_id' => $workflowQuo->id,
                    'document_type' => 'Quotation',
                    'document_id' => $quo->id,
                    'current_step' => 1,
                    'status' => $status === 'accepted' ? 'approved' : 'rejected',
                    'requested_by' => $user->id,
                    'completed_at' => $date->copy()->addHours(rand(1, 4)),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                ApprovalHistory::create([
                    'approval_request_id' => $req->id,
                    'step_order' => 1,
                    'action' => $status === 'accepted' ? 'approved' : 'rejected',
                    'acted_by' => $user->id,
                    'notes' => $status === 'accepted' ? 'Approved based on standard price agreement' : 'Rejected. Requested discount is too high.',
                    'created_at' => $date->copy()->addHours(rand(1, 4)),
                ]);
            }
        }
        $this->command->info('Quotations seeded.');

        // 8. Seed Sales Orders (Last 6 Months)
        $soCount = 55;
        $soStatuses = ['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        $doPrefix = 'DO-' . date('Ym') . '-';
        $doSeq = 1;
        $invoiceSeq = 1;
        $returnSeq = 1;
        $coaSeq = 1;

        for ($i = 0; $i < $soCount; $i++) {
            $date = Carbon::now()->subDays(rand(10, 170)); // Ensure random orders do not fall into current July 2026 month
            $customer = $customers->random();
            $custProducts = $getCustomerProducts($customer->code);
            $status = $soStatuses[array_rand($soStatuses)];

            // Older orders must be delivered or cancelled
            if ($date->lt(Carbon::now()->subDays(30))) {
                $status = rand(0, 10) > 1 ? 'delivered' : 'cancelled';
            }

            $poNumber = 'PO-' . strtoupper($customer->code) . '-' . $date->format('ymd') . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            $so = SalesOrder::create([
                'company_id' => $company->id,
                'so_number' => 'SO-' . $date->format('ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'customer_po_number' => $status === 'draft' ? null : $poNumber,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $date,
                'delivery_date' => $date->copy()->addDays(rand(5, 12)),
                'status' => $status,
                'currency' => 'IDR',
                'exchange_rate' => 1.000000,
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 11,
                'tax_amount' => 0,
                'total' => 0,
                'shipping_name' => $customer->name,
                'shipping_address' => $customer->address,
                'created_by' => $user->id,
                'confirmed_by' => $status !== 'draft' ? $user->id : null,
                'confirmed_at' => $status !== 'draft' ? $date->copy()->addHour() : null,
            ]);

            $subtotal = 0;
            $itemsToSeed = $custProducts->random(min(rand(1, 3), $custProducts->count()));
            $soItems = [];

            foreach ($itemsToSeed as $prod) {
                $qty = rand(200, 800);
                $price = $prod->selling_price > 0 ? $prod->selling_price : rand(50000, 250000);
                $rowTotal = $qty * $price;

                $qtyDelivered = 0;
                if ($status === 'delivered') {
                    $qtyDelivered = $qty;
                } elseif (in_array($status, ['shipped', 'processing'])) {
                    $qtyDelivered = round($qty * (rand(60, 95) / 100), 2); // partial delivery
                }

                $soItem = SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $prod->id,
                    'description' => $prod->name,
                    'qty' => $qty,
                    'unit_id' => $prod->unit_id,
                    'unit_price' => $price,
                    'subtotal' => $rowTotal,
                    'qty_delivered' => $qtyDelivered,
                    'qty_returned' => 0,
                    'qty_invoiced' => 0,
                ]);

                $soItems[] = $soItem;
                $subtotal += $rowTotal;

                // Create Delivery Schedule for this product and customer
                DeliverySchedule::create([
                    'customer_id' => $customer->id,
                    'product_id' => $prod->id,
                    'delivery_date' => $so->delivery_date,
                    'qty_scheduled' => $qty,
                    'po_number' => $so->customer_po_number ?? 'PO-PENDING',
                    'notes' => 'Delivery schedule for SO ' . $so->so_number,
                    'created_by' => $user->id,
                ]);
            }

            $tax = $subtotal * 0.11;
            $so->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax,
            ]);

            // Create Approval Request and history for Sales Order
            if ($status !== 'draft') {
                $req = ApprovalRequest::create([
                    'workflow_id' => $workflowSO->id,
                    'document_type' => 'SalesOrder',
                    'document_id' => $so->id,
                    'current_step' => 1,
                    'status' => 'approved',
                    'requested_by' => $user->id,
                    'completed_at' => $date->copy()->addHours(2),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                ApprovalHistory::create([
                    'approval_request_id' => $req->id,
                    'step_order' => 1,
                    'action' => 'approved',
                    'acted_by' => $user->id,
                    'notes' => 'Approved automatically based on customer credit limits and verified purchase order.',
                    'created_at' => $date->copy()->addHours(2),
                ]);
            }

            // Create Delivery Order (Surat Jalan) if status is shipped, delivered, processing
            if (in_array($status, ['shipped', 'delivered', 'processing'])) {
                $deliveryDate = $date->copy()->addDays(rand(3, 7));
                $doNumber = $doPrefix . str_pad($doSeq++, 4, '0', STR_PAD_LEFT);

                $do = DeliveryOrder::create([
                    'company_id' => $company->id,
                    'do_number' => $doNumber,
                    'sales_order_id' => $so->id,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'delivery_date' => $deliveryDate,
                    'status' => $status === 'delivered' ? 'delivered' : 'shipped',
                    'shipping_name' => $customer->name,
                    'shipping_address' => $customer->address,
                    'driver_name' => ['Agus Supriyanto', 'Budi Prayitno', 'Cahyo Susanto', 'Dedi Hermawan', 'Eko Prasetyo'][rand(0, 4)],
                    'vehicle_number' => ['B', 'L', 'AB', 'D', 'H'][rand(0, 4)] . ' ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90)),
                    'prepared_by' => $user->id,
                    'notes' => 'Delivered via standard logistics partner.',
                ]);

                $doItems = [];
                foreach ($soItems as $soItem) {
                    $doItem = DeliveryOrderItem::create([
                        'delivery_order_id' => $do->id,
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $soItem->product_id,
                        'qty_ordered' => $soItem->qty,
                        'qty_delivered' => $soItem->qty_delivered,
                        'unit_id' => $soItem->unit_id,
                    ]);
                    $doItems[] = $doItem;
                }

                // Create Sales Invoice for Shipped/Delivered
                if (in_array($status, ['shipped', 'delivered'])) {
                    $invoiceDate = $deliveryDate->copy()->addDays(rand(1, 3));
                    $invoiceDueDate = $invoiceDate->copy()->addDays($customer->payment_days);

                    $invStatus = 'sent';
                    if ($status === 'delivered') {
                        $invStatus = $invoiceDueDate->lt(Carbon::now()) ? 'overdue' : 'sent';
                        if (rand(0, 10) > 3) {
                            $invStatus = 'paid';
                        } elseif (rand(0, 10) > 7) {
                            $invStatus = 'partial';
                        }
                    }

                    $invoiceNumber = SalesInvoice::generateInvoiceNumber($customer, $invoiceDate->format('Y-m-d'));

                    $invoice = SalesInvoice::create([
                        'company_id' => $company->id,
                        'invoice_number' => $invoiceNumber,
                        'sales_order_id' => $so->id,
                        'customer_id' => $customer->id,
                        'invoice_date' => $invoiceDate,
                        'due_date' => $invoiceDueDate,
                        'status' => $invStatus,
                        'subtotal' => 0,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total' => 0,
                        'paid_amount' => 0,
                        'balance' => 0,
                        'notes' => 'Tax invoice generated automatically.',
                        'created_by' => $user->id,
                    ]);

                    // Seed COA Document for delivered steel
                    \App\Models\CoaDocument::create([
                        'coa_number' => 'COA-' . $invoiceDate->format('Ym') . '-' . str_pad($coaSeq++, 4, '0', STR_PAD_LEFT),
                        'sales_order_id' => $so->id,
                        'customer_id' => $customer->id,
                        'batch_number' => 'BCH-' . strtoupper(Str::random(6)),
                        'issued_date' => $invoiceDate,
                        'file_path' => 'coa/coa_mock_' . strtolower($customer->code) . '_' . $so->id . '.pdf',
                    ]);

                    $invSubtotal = 0;
                    foreach ($soItems as $index => $soItem) {
                        $invItemQty = $soItem->qty_delivered;
                        $invItemTotal = $invItemQty * $soItem->unit_price;

                        SalesInvoiceItem::create([
                            'sales_invoice_id' => $invoice->id,
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $soItem->product_id,
                            'description' => $soItem->description,
                            'qty' => $invItemQty,
                            'unit_id' => $soItem->unit_id,
                            'unit_price' => $soItem->unit_price,
                            'discount_percent' => 0,
                            'discount_amount' => 0,
                            'subtotal' => $invItemTotal,
                            'delivery_order_id' => $do->id,
                        ]);

                        $invSubtotal += $invItemTotal;

                        // Update SO Item invoiced quantity
                        $soItem->update(['qty_invoiced' => $invItemQty]);
                    }

                    $invTax = $invSubtotal * 0.11;
                    $invTotal = $invSubtotal + $invTax;

                    $paidAmount = 0;
                    if ($invStatus === 'paid') {
                        $paidAmount = $invTotal;
                    } elseif ($invStatus === 'partial') {
                        $paidAmount = round($invTotal * (rand(20, 60) / 100), 2);
                    }

                    $invoice->update([
                        'subtotal' => $invSubtotal,
                        'tax_amount' => $invTax,
                        'total' => $invTotal,
                        'paid_amount' => $paidAmount,
                        'balance' => $invTotal - $paidAmount,
                    ]);

                    // Generate Sales Payment if paid/partial
                    if ($paidAmount > 0) {
                        SalesPayment::create([
                            'sales_invoice_id' => $invoice->id,
                            'payment_number' => SalesPayment::generatePaymentNumber(),
                            'amount' => $paidAmount,
                            'payment_date' => $invoiceDate->copy()->addDays(rand(5, 20)),
                            'payment_method' => 'Transfer',
                            'bank_name' => $customer->bank_name ?? 'Bank Mandiri',
                            'account_number' => $customer->account_number ?? '1234567890',
                            'notes' => 'Customer payment received via bank transfer.',
                            'created_by' => $user->id,
                        ]);
                    }

                    // Generate a Sales Return for a few cases
                    if ($status === 'delivered' && rand(0, 100) > 92) {
                        $returnDate = $deliveryDate->copy()->addDays(rand(3, 7));
                        $returnItem = $soItems[0];
                        $returnQty = rand(10, 30);
                        $returnTotal = $returnQty * $returnItem->unit_price;

                        $sr = SalesReturn::create([
                            'number' => 'RET-' . $returnDate->format('ym') . '-' . str_pad($returnSeq++, 4, '0', STR_PAD_LEFT),
                            'sales_invoice_id' => $invoice->id,
                            'sales_order_id' => $so->id,
                            'customer_id' => $customer->id,
                            'warehouse_id' => $warehouse->id,
                            'return_date' => $returnDate,
                            'reason' => ['Thickness out of tolerance (+0.15mm)', 'Surface scratches', 'Laser welding deviation', 'Warping defect'][rand(0, 3)],
                            'status' => 'completed',
                            'total_amount' => $returnTotal,
                            'created_by' => $user->id,
                        ]);

                        SalesReturnItem::create([
                            'sales_return_id' => $sr->id,
                            'product_id' => $returnItem->product_id,
                            'qty' => $returnQty,
                            'unit_price' => $returnItem->unit_price,
                            'total_price' => $returnTotal,
                        ]);

                        // Update SO item returned qty
                        $returnItem->update([
                            'qty_returned' => $returnQty,
                        ]);
                    }
                }
            }
        }

        $this->command->info(SalesOrder::count() . ' Sales Orders seeded with related DOs, Invoices, Payments, and Returns.');

        // 8.5 Seed explicit July 2026 Sales Orders, Delivery Schedules, DOs and Invoices for monitoring matrix
        $this->command->info('Seeding explicit July 2026 schedules and deliveries for all customers...');
        
        $julySOData = [
            // CUST-TMMIN (Toyota)
            ['cust' => 'CUST-TMMIN', 'prod' => 'FG-BLNK-FENDER-CR12', 'sch_date' => '2026-07-01', 'sch_qty' => 500, 'act_qty' => 500, 'status' => 'delivered'],
            ['cust' => 'CUST-TMMIN', 'prod' => 'FG-BLNK-DISCBRK-HR26', 'sch_date' => '2026-07-05', 'sch_qty' => 600, 'act_qty' => 580, 'status' => 'delivered'],
            ['cust' => 'CUST-TMMIN', 'prod' => 'FG-TWB-PILLAR-03', 'sch_date' => '2026-07-12', 'sch_qty' => 450, 'act_qty' => 0, 'status' => 'confirmed'],
            
            // CUST-ADM (Daihatsu)
            ['cust' => 'CUST-ADM', 'prod' => 'FG-BLNK-HOODIN-GA08', 'sch_date' => '2026-07-04', 'sch_qty' => 433, 'act_qty' => 433, 'status' => 'delivered'],
            ['cust' => 'CUST-ADM', 'prod' => 'FG-BLNK-HOODIN-GA08', 'sch_date' => '2026-07-08', 'sch_qty' => 446, 'act_qty' => 420, 'status' => 'delivered'],
            ['cust' => 'CUST-ADM', 'prod' => 'FG-BLNK-ROOF-CR16', 'sch_date' => '2026-07-08', 'sch_qty' => 571, 'act_qty' => 0, 'status' => 'confirmed'],
            ['cust' => 'CUST-ADM', 'prod' => 'FG-BLNK-ROOF-CR16', 'sch_date' => '2026-07-15', 'sch_qty' => 500, 'act_qty' => 500, 'status' => 'delivered'],
            
            // CUST-HPM (Honda)
            ['cust' => 'CUST-HPM', 'prod' => 'FG-TWB-DOORPANEL-01', 'sch_date' => '2026-07-02', 'sch_qty' => 300, 'act_qty' => 300, 'status' => 'delivered'],
            ['cust' => 'CUST-HPM', 'prod' => 'FG-TWB-SIDEPANEL-02', 'sch_date' => '2026-07-07', 'sch_qty' => 400, 'act_qty' => 380, 'status' => 'delivered'],
            ['cust' => 'CUST-HPM', 'prod' => 'FG-TWB-DOORPANEL-01', 'sch_date' => '2026-07-20', 'sch_qty' => 350, 'act_qty' => 0, 'status' => 'confirmed'],

            // CUST-SIM (Suzuki)
            ['cust' => 'CUST-SIM', 'prod' => 'FG-BLNK-FENDER-CR12', 'sch_date' => '2026-07-03', 'sch_qty' => 400, 'act_qty' => 400, 'status' => 'delivered'],
            ['cust' => 'CUST-SIM', 'prod' => 'FG-BLNK-HOODIN-GA08', 'sch_date' => '2026-07-09', 'sch_qty' => 500, 'act_qty' => 490, 'status' => 'delivered'],
            
            // CUST-MMKI (Mitsubishi)
            ['cust' => 'CUST-MMKI', 'prod' => 'FG-BLNK-FENDER-CR12', 'sch_date' => '2026-07-02', 'sch_qty' => 569, 'act_qty' => 569, 'status' => 'delivered'],
            ['cust' => 'CUST-MMKI', 'prod' => 'FG-TWB-PILLAR-03', 'sch_date' => '2026-07-02', 'sch_qty' => 652, 'act_qty' => 600, 'status' => 'delivered'],
            ['cust' => 'CUST-MMKI', 'prod' => 'FG-BLNK-FENDER-CR12', 'sch_date' => '2026-07-18', 'sch_qty' => 500, 'act_qty' => 0, 'status' => 'confirmed'],

            // CUST-AOP (Astra Otoparts)
            ['cust' => 'CUST-AOP', 'prod' => 'FG-BLNK-DISCBRK-HR26', 'sch_date' => '2026-07-06', 'sch_qty' => 350, 'act_qty' => 350, 'status' => 'delivered'],
            ['cust' => 'CUST-AOP', 'prod' => 'FG-BLNK-DISCBRK-HR26', 'sch_date' => '2026-07-14', 'sch_qty' => 400, 'act_qty' => 390, 'status' => 'delivered'],

            // CUST-PMI (Panasonic)
            ['cust' => 'CUST-PMI', 'prod' => 'FG-COMP-ACBRACKET-2.0', 'sch_date' => '2026-07-03', 'sch_qty' => 800, 'act_qty' => 800, 'status' => 'delivered'],
            ['cust' => 'CUST-PMI', 'prod' => 'FG-COMP-ACBRACKET-2.0', 'sch_date' => '2026-07-11', 'sch_qty' => 900, 'act_qty' => 0, 'status' => 'confirmed'],

            // CUST-SEID (Sharp)
            ['cust' => 'CUST-SEID', 'prod' => 'FG-COMP-ACBRACKET-2.0', 'sch_date' => '2026-07-06', 'sch_qty' => 319, 'act_qty' => 198, 'status' => 'delivered'],
            ['cust' => 'CUST-SEID', 'prod' => 'FG-COMP-ACBRACKET-2.0', 'sch_date' => '2026-07-22', 'sch_qty' => 400, 'act_qty' => 400, 'status' => 'delivered'],

            // CUST-SPIN (Spindo)
            ['cust' => 'CUST-SPIN', 'prod' => 'SLIT-HR-SPHC-2.0-150', 'sch_date' => '2026-07-04', 'sch_qty' => 700, 'act_qty' => 700, 'status' => 'delivered'],
            ['cust' => 'CUST-SPIN', 'prod' => 'SLIT-CR-SPCC-1.2-120', 'sch_date' => '2026-07-10', 'sch_qty' => 500, 'act_qty' => 500, 'status' => 'delivered'],

            // CUST-WIKA (Waskita)
            ['cust' => 'CUST-WIKA', 'prod' => 'SHT-HR-SPHC-2.0-1219x2438', 'sch_date' => '2026-07-06', 'sch_qty' => 500, 'act_qty' => 450, 'status' => 'delivered'],
            ['cust' => 'CUST-WIKA', 'prod' => 'SHT-CR-SPCC-1.2-1219x2438', 'sch_date' => '2026-07-13', 'sch_qty' => 400, 'act_qty' => 0, 'status' => 'confirmed']
        ];

        foreach ($julySOData as $data) {
            $customer = Customer::where('code', $data['cust'])->first();
            $product = Product::where('sku', $data['prod'])->first();
            if (!$customer || !$product) continue;

            $schDate = Carbon::parse($data['sch_date']);
            $orderDate = $schDate->copy()->subDays(rand(5, 7));

            // If the delivery is scheduled in the future (after July 3rd, 2026),
            // it cannot be already delivered. Change status to confirmed and actual qty to 0.
            $status = $data['status'];
            $actQty = $data['act_qty'];
            if ($schDate->gt(Carbon::create(2026, 7, 3))) {
                $status = 'confirmed';
                $actQty = 0;
            }

            // Create Sales Order
            $poNumber = 'PO-' . strtoupper($customer->code) . '-' . $orderDate->format('ymd') . '-JUL' . rand(10, 99);
            $so = SalesOrder::create([
                'company_id' => $company->id,
                'so_number' => 'SO-' . $orderDate->format('ym') . '-JUL' . str_pad($invoiceSeq++, 4, '0', STR_PAD_LEFT),
                'customer_po_number' => $poNumber,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $orderDate,
                'delivery_date' => $schDate,
                'status' => $status,
                'currency' => 'IDR',
                'exchange_rate' => 1.000000,
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 11,
                'tax_amount' => 0,
                'total' => 0,
                'shipping_name' => $customer->name,
                'shipping_address' => $customer->address,
                'created_by' => $user->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => $orderDate->copy()->addHour(),
            ]);

            $price = $product->selling_price > 0 ? $product->selling_price : rand(50000, 250000);
            $rowTotal = $data['sch_qty'] * $price;

            $soItem = SalesOrderItem::create([
                'sales_order_id' => $so->id,
                'product_id' => $product->id,
                'description' => $product->name,
                'qty' => $data['sch_qty'],
                'unit_id' => $product->unit_id,
                'unit_price' => $price,
                'subtotal' => $rowTotal,
                'qty_delivered' => $actQty,
                'qty_returned' => 0,
                'qty_invoiced' => 0,
            ]);

            $tax = $rowTotal * 0.11;
            $so->update([
                'subtotal' => $rowTotal,
                'tax_amount' => $tax,
                'total' => $rowTotal + $tax,
            ]);

            // Create Delivery Schedule
            $ds = DeliverySchedule::create([
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => $schDate,
                'qty_scheduled' => $data['sch_qty'],
                'po_number' => $so->customer_po_number,
                'notes' => 'July matrix delivery schedule for SO ' . $so->so_number,
                'created_by' => $user->id,
            ]);

            // Create DO if delivered/shipped
            if ($actQty > 0) {
                $doNumber = $doPrefix . 'JUL' . str_pad($doSeq++, 4, '0', STR_PAD_LEFT);
                $do = DeliveryOrder::create([
                    'company_id' => $company->id,
                    'do_number' => $doNumber,
                    'sales_order_id' => $so->id,
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'delivery_date' => $schDate,
                    'status' => 'delivered',
                    'shipping_name' => $customer->name,
                    'shipping_address' => $customer->address,
                    'driver_name' => ['Agus Supriyanto', 'Budi Prayitno', 'Cahyo Susanto', 'Dedi Hermawan', 'Eko Prasetyo'][rand(0, 4)],
                    'vehicle_number' => ['B', 'L', 'AB', 'D', 'H'][rand(0, 4)] . ' ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90)),
                    'prepared_by' => $user->id,
                    'notes' => 'Delivered for July monitoring matrix.',
                ]);

                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $product->id,
                    'qty_ordered' => $data['sch_qty'],
                    'qty_delivered' => $actQty,
                    'unit_id' => $product->unit_id,
                ]);

                // Create Invoice
                $invoiceDate = $schDate->copy()->addDays(rand(1, 2));
                $invoiceDueDate = $invoiceDate->copy()->addDays($customer->payment_days);
                $invoiceNumber = SalesInvoice::generateInvoiceNumber($customer, $invoiceDate->format('Y-m-d'));

                $invoice = SalesInvoice::create([
                    'company_id' => $company->id,
                    'invoice_number' => $invoiceNumber,
                    'sales_order_id' => $so->id,
                    'customer_id' => $customer->id,
                    'invoice_date' => $invoiceDate,
                    'due_date' => $invoiceDueDate,
                    'status' => 'paid',
                    'subtotal' => $data['act_qty'] * $price,
                    'discount_amount' => 0,
                    'tax_amount' => ($data['act_qty'] * $price) * 0.11,
                    'total' => ($data['act_qty'] * $price) * 1.11,
                    'paid_amount' => ($data['act_qty'] * $price) * 1.11,
                    'balance' => 0,
                    'notes' => 'Tax invoice generated automatically.',
                    'created_by' => $user->id,
                ]);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => $data['act_qty'],
                    'unit_id' => $product->unit_id,
                    'unit_price' => $price,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'subtotal' => $data['act_qty'] * $price,
                    'delivery_order_id' => $do->id,
                ]);

                // Seed COA Document
                \App\Models\CoaDocument::create([
                    'coa_number' => 'COA-' . $invoiceDate->format('Ym') . '-JUL' . str_pad($coaSeq++, 4, '0', STR_PAD_LEFT),
                    'sales_order_id' => $so->id,
                    'customer_id' => $customer->id,
                    'batch_number' => 'BCH-' . strtoupper(Str::random(6)),
                    'issued_date' => $invoiceDate,
                    'file_path' => 'coa/coa_mock_' . strtolower($customer->code) . '_' . $so->id . '.pdf',
                ]);

                $soItem->update(['qty_invoiced' => $data['act_qty']]);
            }
        }

        $this->command->info(SalesOrder::count() . ' Sales Orders seeded with related DOs, Invoices, Payments, and Returns.');

        // 9. Seed Sales Visits
        $salesUsers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Sales Manager', 'Super Admin']);
        })->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = collect([$user]);
        }

        $purposes = [
            'Product Presentation & Demo',
            'Contract Negotiation',
            'Follow-up on Proposal',
            'Routine Customer Feedback',
            'Relationship Building Lunch',
            'Technical Assessment & Site Survey',
            'New Feature Pitching'
        ];

        // Completed Visits
        for ($i = 0; $i < 15; $i++) {
            $sales = $salesUsers->random();
            $customer = $customers->random();
            $plannedDate = Carbon::now()->subDays(rand(1, 20))->setHour(rand(9, 15))->setMinute(0);

            // Coordinates for Java areas (Karawang/Jakarta/Bekasi/Surabaya)
            $lat = -6.2 + (rand(-100, 100) / 1000.0);
            $lng = 106.8 + (rand(-100, 100) / 1000.0);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'purpose' => $purposes[array_rand($purposes)],
                'notes' => 'Discussed raw materials supply and processed finished goods specs.',
                'status' => 'completed',
                'planned_at' => $plannedDate,
                'check_in_at' => $plannedDate->copy()->addMinutes(rand(5, 20)),
                'check_out_at' => $plannedDate->copy()->addMinutes(rand(60, 120)),
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'check_in_address' => $customer->address,
                'check_out_lat' => $lat + 0.0001,
                'check_out_lng' => $lng + 0.0001,
                'check_out_address' => $customer->address,
                'summary' => 'Client is satisfied with PT USC product quality. Moving forward with the next batch order.'
            ]);
        }

        // Active/Checked-In
        for ($i = 0; $i < 2; $i++) {
            $sales = $salesUsers->random();
            $customer = $customers->random();
            $plannedDate = Carbon::now()->subHours(rand(1, 2));
            $lat = -6.2 + (rand(-100, 100) / 1000.0);
            $lng = 106.8 + (rand(-100, 100) / 1000.0);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'purpose' => 'Technical Assessment & Site Survey',
                'notes' => 'Reviewing blank plates tolerance on client welding line.',
                'status' => 'checked_in',
                'planned_at' => $plannedDate,
                'check_in_at' => $plannedDate->copy()->addMinutes(10),
                'check_in_lat' => $lat,
                'check_in_lng' => $lng,
                'check_in_address' => $customer->address,
            ]);
        }

        // Planned
        for ($i = 0; $i < 8; $i++) {
            $sales = $salesUsers->random();
            $customer = $customers->random();
            $plannedDate = Carbon::now()->addDays(rand(1, 10))->setHour(rand(9, 16))->setMinute(0);

            SalesVisit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'purpose' => $purposes[array_rand($purposes)],
                'notes' => 'Quarterly business review and discussion on next contract expansion.',
                'status' => 'planned',
                'planned_at' => $plannedDate,
            ]);
        }

        $this->command->info(SalesVisit::count() . ' Sales Visits seeded.');
        $this->command->info('Sales module dummy data seeded successfully!');
    }
}
