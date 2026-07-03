<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DocumentNumberingSeeder::class,
            TaxRateSeeder::class,
            DemoDataSeeder::class,
            SalesSeeder::class,
            PurchasingSeeder::class,
            LogisticsSeeder::class,
            HrSeeder::class,
            LinkUsersToEmployeesSeeder::class,
            SystemPreferencesSeeder::class,
            UsicsDetailedSeeder::class,

            UatSalesSeeder::class,
            UatPurchasingSeeder::class,
            UatInventorySeeder::class,
            UatManufacturingSeeder::class,
            UatCrmSeeder::class,
            CrmDummySeeder::class,
            SalesVisitSeeder::class,
            DraftSoDoSeeder::class,
            UatLogisticsSeeder::class,
            QcDummyDataSeeder::class,
            GaDummySeeder::class,
            AssignActivityCauserSeeder::class,
        ]);
    }
}
