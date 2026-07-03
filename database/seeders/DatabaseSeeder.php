<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DemoDataSeeder::class,
            TaxRateSeeder::class,
            SystemPreferencesSeeder::class,
            UscProductSeeder::class,
            UscMachineSeeder::class,
            LinkUsersToEmployeesSeeder::class,
            RoleSeeder::class,
            UscStorageLocationSeeder::class,
            DocumentNumberingSeeder::class,
            UsicsDetailedSeeder::class,
            
            // Transactional Seeders
            SalesSeeder::class,
            PurchasingSeeder::class,
            ProductionIntelligenceSeeder::class,
            LogisticsSeeder::class,
            MaintenanceDummySeeder::class,
            MeetingDummySeeder::class,
            FinanceDummySeeder::class,
            HrJuneDummyDataSeeder::class,

            // UAT Scenarios & Custom Seeders
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
            ActivityLogDummySeeder::class,
            AssignActivityCauserSeeder::class,
        ]);
    }
}
