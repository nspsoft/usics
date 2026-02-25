<?php

namespace Database\Seeders;

use App\Models\DocumentNumbering;
use Illuminate\Database\Seeder;

class DocumentNumberingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // Returns
            [
                'module' => 'sales',
                'code' => 'sales_return',
                'name' => 'Sales Return',
                'prefix' => 'RET',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'purchasing',
                'code' => 'purchase_return',
                'name' => 'Purchase Return',
                'prefix' => 'PRT',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            // Sales
            [
                'module' => 'sales',
                'code' => 'sales_order',
                'name' => 'Sales Order',
                'prefix' => 'SO',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'sales',
                'code' => 'sales_invoice',
                'name' => 'Sales Invoice',
                'prefix' => 'INV',
                'format' => '{NUMBER}/INV/JRI-{CUST_CODE}/{ROMAN_MONTH}/{y}',
                'padding' => 4,
            ],
            [
                'module' => 'sales',
                'code' => 'delivery_order',
                'name' => 'Delivery Order',
                'prefix' => 'DO',
                'format' => '{NUMBER}/DO/JRI-{CUST_CODE}/{ROMAN_MONTH}/{y}',
                'padding' => 3,
            ],
            [
                'module' => 'sales',
                'code' => 'quotation',
                'name' => 'Quotation',
                'prefix' => 'QUOT',
                'format' => '{NUMBER}/QUOT/JRI-{CUST_CODE}/{ROMAN_MONTH}/{y}',
                'padding' => 3,
            ],
            // Purchasing
            [
                'module' => 'purchasing',
                'code' => 'purchase_request',
                'name' => 'Purchase Request',
                'prefix' => 'PR',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'purchasing',
                'code' => 'purchase_order',
                'name' => 'Purchase Order',
                'prefix' => 'PRCH',
                'format' => 'JRI-{SUPP_CODE}/{y}/{m}/PRCH/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'purchasing',
                'code' => 'goods_receipt',
                'name' => 'Goods Receipt',
                'prefix' => 'GR',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            // Manufacturing
            [
                'module' => 'manufacturing',
                'code' => 'work_order',
                'name' => 'Work Order',
                'prefix' => 'WO',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'manufacturing',
                'code' => 'production_entry',
                'name' => 'Production Entry',
                'prefix' => 'PROD',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            // Subcontractor
            [
                'module' => 'manufacturing',
                'code' => 'subcont_delivery',
                'name' => 'Surat Jalan Subcont',
                'prefix' => 'SUB',
                'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                'padding' => 3,
            ],
            [
                'module' => 'manufacturing',
                'code' => 'subcont_receipt',
                'name' => 'Laporan Penerimaan (Subcont)',
                'prefix' => 'LPB',
                'format' => '{PREFIX}-{NUMBER}',
                'padding' => 6,
            ],
        ];

        foreach ($defaults as $config) {
            DocumentNumbering::updateOrCreate(
                ['code' => $config['code']],
                $config
            );
        }
    }
}
