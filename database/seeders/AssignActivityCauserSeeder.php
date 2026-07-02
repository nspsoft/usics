<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class AssignActivityCauserSeeder extends Seeder
{
    public function run()
    {
        // Define user mappings by model classes
        $mappings = [
            // Sales & CRM -> Sales (id 2) or CRM (id 6)
            'App\Models\Customer' => [2, 6],
            'App\Models\CustomerContact' => [2, 6],
            'App\Models\Quotation' => [2],
            'App\Models\SalesOrder' => [2],
            'App\Models\SalesOrderItem' => [2],
            'App\Models\SalesInvoice' => [2],
            'App\Models\SalesReturn' => [2],
            'App\Models\CRM\Lead' => [6],
            'App\Models\CRM\Opportunity' => [6],
            'App\Models\CRM\Campaign' => [6],
            'App\Models\CRM\SalesVisit' => [2, 6],

            // Purchasing -> Purchasing (id 3)
            'App\Models\Supplier' => [3],
            'App\Models\SupplierContact' => [3],
            'App\Models\PurchaseRequest' => [3],
            'App\Models\PurchaseOrder' => [3],
            'App\Models\GoodsReceipt' => [3],
            'App\Models\PurchaseInvoice' => [3],
            'App\Models\PurchaseReturn' => [3],

            // Inventory -> Inventory (id 4)
            'App\Models\Product' => [4],
            'App\Models\Warehouse' => [4],
            'App\Models\StockOpname' => [4],

            // Manufacturing -> Manufacturing (id 5) or Produksi (id 12)
            'App\Models\Machine' => [5, 12],
            'App\Models\Bom' => [5, 12],
            'App\Models\WorkOrder' => [5, 12],
            'App\Models\ProductionEntry' => [12],
            'App\Models\MaterialConsumption' => [12],

            // Logistics -> Logistics (id 7) or Delivery (id 11)
            'App\Models\DeliveryOrder' => [7, 11],
            'App\Models\DeliveryOrderItem' => [7, 11],

            // QC -> QC (id 8) or Noviardi (id 17)
            'App\Models\QcInspection' => [8, 17],
            'App\Models\QcInspectionItem' => [8, 17],
            'App\Models\QcMasterPoint' => [8, 17],
            'App\Models\MtcDocument' => [8, 17],
            'App\Models\MtcItem' => [8, 17],
            'App\Models\NonConformanceReport' => [8, 17],

            // Finance -> Finance (id 9) or Ahmad Hasanudin (id 18)
            'App\Models\Payroll' => [9, 18],

            // HR -> HR (id 10)
            'App\Models\Department' => [10],
            'App\Models\Position' => [10],
            'App\Models\Employee' => [10],
            'App\Models\Attendance' => [10],
            'App\Models\HR\Reimbursement' => [10],
        ];

        // Retrieve existing activities
        $activities = Activity::all();

        // Top user IDs for general/auth activities
        $generalUserIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 17, 18];

        $updatedCount = 0;

        foreach ($activities as $activity) {
            $userIds = null;
            if ($activity->subject_type && isset($mappings[$activity->subject_type])) {
                $userIds = $mappings[$activity->subject_type];
            }

            // Fallback for null subject_type or unmapped ones (e.g. auth logs)
            if (!$userIds) {
                $userIds = $generalUserIds;
            }

            // Pick a random user from the allowed list
            $userId = $userIds[array_rand($userIds)];

            // Determine realistic date based on subject model attributes
            $date = null;
            try {
                if ($activity->subject) {
                    $date = $activity->subject->created_at 
                        ?? $activity->subject->order_date 
                        ?? $activity->subject->inspection_date 
                        ?? $activity->subject->planned_at 
                        ?? null;
                }
            } catch (\Exception $e) {
                // Ignore missing attribute/relation exceptions
            }

            if (!$date) {
                // Fallback: Random date between April 1st and today (approx 92 days)
                $date = \Carbon\Carbon::create(2026, 4, 1)->addDays(rand(0, 92))->setHour(rand(8, 20))->setMinute(rand(0, 59))->setSecond(rand(0, 59));
            } else {
                // Parse date and add/subtract random minutes to make it slightly offset
                $date = \Carbon\Carbon::parse($date)->addMinutes(rand(-30, 30));
            }

            // Update causer and timestamps (bypassing model events/touches if needed)
            $activity->update([
                'causer_id' => $userId,
                'causer_type' => 'App\Models\User',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $updatedCount++;
        }

        $this->command->info("Successfully updated {$updatedCount} activity logs to assign realistic causers.");
    }
}
