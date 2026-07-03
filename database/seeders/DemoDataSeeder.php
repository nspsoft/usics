<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Company with custom active settings
        $company = Company::firstOrCreate(
            ['code' => 'USC-ID'],
            [
                'name' => 'USICS',
                'legal_name' => 'PT United Steel Center Indonesia',
                'address' => 'Jl. Mitra Raya Selatan II - Blok F No.1 parung Mulya, Karawang Ciampel Kab. Karawang Jawa Barat Indonesia',
                'city' => 'Karawang',
                'state' => 'Jawa Barat',
                'postal_code' => '41361',
                'country' => 'ID',
                'phone' => '+62 267 440707',
                'email' => 'info@usc-indonesia.co.id',
                'website' => 'https://www.usc-indonesia.co.id/',
                'tax_id' => '01.002.888.8-092.000',
                'logo' => '/storage/logos/2cK92DoCbpDqWmzSfROExiH2Hm8M0IZfODDA0vMb.png',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'settings' => [
                    'ai' => [
                        'ai_driver' => 'gemini',
                        'ollama_url' => 'http://localhost:11434',
                        'gemini_model' => 'gemini-2.5-flash',
                        'ollama_model' => 'llama3',
                        'gemini_api_key' => 'AQ.Ab8RN6KoR2sCaDkSZUgM_RCTjXzq7PeXfzPPsif9uDP8IRRxFQ',
                        'openrouter_model' => 'google/gemini-2.5-flash',
                        'openrouter_api_key' => '',
                    ],
                    'email' => [
                        'from_name' => '',
                        'imap_host' => '',
                        'imap_port' => '993',
                        'smtp_host' => '',
                        'smtp_port' => '587',
                        'from_address' => '',
                        'imap_password' => '',
                        'imap_username' => 'admin@usc-indonesia.co.id',
                        'smtp_password' => '',
                        'smtp_username' => '',
                        'imap_encryption' => 'ssl',
                        'smtp_encryption' => 'tls',
                    ],
                    'emeterai' => [
                        'enabled' => '',
                        'client_id' => '',
                        'secret_key' => '',
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Create Admin User
        $admin = User::first();
        if ($admin) {
            $admin->update([
                'company_id' => $company->id,
                'name' => 'Administrator',
            ]);
        }

        // Create Units
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'],
            ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg'],
            ['code' => 'MTR', 'name' => 'Meter', 'symbol' => 'm'],
            ['code' => 'LTR', 'name' => 'Liter', 'symbol' => 'L'],
            ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box'],
            ['code' => 'SET', 'name' => 'Set', 'symbol' => 'set'],
            ['code' => 'ROLL', 'name' => 'Roll', 'symbol' => 'roll'],
            ['code' => 'SHT', 'name' => 'Sheet', 'symbol' => 'sht'],
        ];

        foreach ($units as $unit) {
            Unit::create([
                'company_id' => $company->id,
                ...$unit,
                'is_active' => true,
            ]);
        }

        // Create Categories
        $categories = [
            ['code' => 'RM', 'name' => 'Raw Materials', 'type' => 'product'],
            ['code' => 'WIP', 'name' => 'Work in Progress', 'type' => 'product'],
            ['code' => 'FG', 'name' => 'Finished Goods', 'type' => 'product'],
            ['code' => 'SP', 'name' => 'Spare Parts', 'type' => 'product'],
            ['code' => 'PKG', 'name' => 'Packaging', 'type' => 'product'],
            ['code' => 'CONS', 'name' => 'Consumables', 'type' => 'product'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'company_id' => $company->id,
                ...$category,
                'is_active' => true,
            ]);
        }

        // Create Warehouses
        $warehouses = [
            [
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'address' => 'Jl. Mitra Raya Selatan II - Blok F No.1 parung Mulya, Karawang Ciampel Kab. Karawang Jawa Barat Indonesia',
                'city' => 'Karawang',
                'type' => 'warehouse',
                'is_default' => true,
            ],
            [
                'code' => 'WH-RM',
                'name' => 'Raw Material Warehouse',
                'address' => 'Jl. Mitra Raya Selatan II - Blok F No.1 parung Mulya, Karawang Ciampel Kab. Karawang Jawa Barat Indonesia',
                'city' => 'Karawang',
                'type' => 'warehouse',
                'is_default' => false,
            ],
            [
                'code' => 'WH-PROD',
                'name' => 'Production Area',
                'address' => 'Jl. Mitra Raya Selatan II - Blok F No.1 parung Mulya, Karawang Ciampel Kab. Karawang Jawa Barat Indonesia',
                'city' => 'Karawang',
                'type' => 'production',
                'is_default' => false,
            ],
            [
                'code' => 'WH-FG',
                'name' => 'Finished Goods Warehouse',
                'address' => 'Jl. Mitra Raya Selatan II - Blok F No.1 parung Mulya, Karawang Ciampel Kab. Karawang Jawa Barat Indonesia',
                'city' => 'Karawang',
                'type' => 'warehouse',
                'is_default' => false,
            ],
        ];

        $createdWarehouses = [];
        foreach ($warehouses as $warehouse) {
            $createdWarehouses[] = Warehouse::create([
                'company_id' => $company->id,
                ...$warehouse,
                'is_active' => true,
            ]);
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Created: 1 Company (USICS / PT United Steel Center Indonesia), ' . count($units) . ' Units, ' . count($categories) . ' Categories, ' . count($warehouses) . ' Warehouses');
    }
}
