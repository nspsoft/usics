<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\BankStatementTransaction;
use App\Models\Customer;
use App\Models\Supplier;

class ReconciliationDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            throw new \RuntimeException('Error: Seeder data dummy ini tidak boleh dijalankan di environment Production!');
        }

        // 1. Get valid customer, supplier, sales order, purchase order, company
        $customer = Customer::first();
        $supplier = Supplier::first();
        $salesOrder = \App\Models\SalesOrder::first();
        $purchaseOrder = \App\Models\PurchaseOrder::first();
        $company = \App\Models\Company::first();

        if (!$customer || !$supplier) {
            echo "Error: Customer atau Supplier tidak ditemukan di database." . PHP_EOL;
            return;
        }

        $salesOrderId = $salesOrder?->id;
        $purchaseOrderId = $purchaseOrder?->id;
        $companyId = $company?->id;

        // 2. Create Dummy Sales Invoices
        SalesInvoice::create([
            'invoice_number' => 'DEMO-INV-001',
            'customer_id' => $customer->id,
            'sales_order_id' => $salesOrderId,
            'company_id' => $companyId,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'issued',
            'subtotal' => 15000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 15000000,
            'paid_amount' => 0,
            'balance' => 15000000,
            'created_by' => 1,
            'notes' => 'Sales Invoice Dummy untuk Simulasi Rekonsiliasi',
        ]);

        SalesInvoice::create([
            'invoice_number' => 'DEMO-INV-002',
            'customer_id' => $customer->id,
            'sales_order_id' => $salesOrderId,
            'company_id' => $companyId,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'issued',
            'subtotal' => 27500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 27500000,
            'paid_amount' => 0,
            'balance' => 27500000,
            'created_by' => 1,
            'notes' => 'Sales Invoice Dummy untuk Simulasi Rekonsiliasi',
        ]);

        // 3. Create Dummy Purchase Invoices
        PurchaseInvoice::create([
            'invoice_number' => 'DEMO-PINV-001',
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrderId,
            'company_id' => $companyId,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'unpaid',
            'currency' => 'IDR',
            'exchange_rate' => 1.0,
            'subtotal' => 18750000,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'discount_total' => 0,
            'total_amount' => 18750000,
            'paid_amount' => 0,
            'created_by' => 1,
            'notes' => 'Purchase Invoice Dummy untuk Simulasi Rekonsiliasi',
        ]);

        PurchaseInvoice::create([
            'invoice_number' => 'DEMO-PINV-002',
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrderId,
            'company_id' => $companyId,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'unpaid',
            'currency' => 'IDR',
            'exchange_rate' => 1.0,
            'subtotal' => 35000000,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'discount_total' => 0,
            'total_amount' => 35000000,
            'paid_amount' => 0,
            'created_by' => 1,
            'notes' => 'Purchase Invoice Dummy untuk Simulasi Rekonsiliasi',
        ]);

        // 4. Create Bank Statement Transactions
        $transactions = [
            [
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'KREDIT TRANSFER DARI CUSTOMER REKONSIL DEMO-INV-001',
                'amount' => 15000000,
                'type' => 'CR',
                'bank_name' => 'BCA',
            ],
            [
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'KREDIT TRANSFER MANDIRI PELUNASAN',
                'amount' => 27500000,
                'type' => 'CR',
                'bank_name' => 'Mandiri',
            ],
            [
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'DEBIT TRANSFER KELUAR UNTUK DEMO-PINV-001',
                'amount' => 18750000,
                'type' => 'DB',
                'bank_name' => 'BCA',
            ],
            [
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'DEBIT TRANSFER MANDIRI KELUAR',
                'amount' => 35000000,
                'type' => 'DB',
                'bank_name' => 'Mandiri',
            ],
        ];

        foreach ($transactions as $tr) {
            $hash = BankStatementTransaction::generateHash(
                $tr['transaction_date'],
                $tr['description'],
                $tr['amount'],
                $tr['type']
            );

            if (!BankStatementTransaction::where('hash', $hash)->exists()) {
                BankStatementTransaction::create([
                    'transaction_date' => $tr['transaction_date'],
                    'description' => $tr['description'],
                    'amount' => $tr['amount'],
                    'type' => $tr['type'],
                    'bank_name' => $tr['bank_name'],
                    'hash' => $hash,
                    'created_by' => 1,
                ]);
            }
        }
    }
}
