<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ActivityLogDummySeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Generating dummy activity logs...');

        // Clean out any existing logs
        Activity::truncate();

        // Mappings of models to causers (user IDs)
        $mappings = [
            'App\Models\SalesOrder' => [2],
            'App\Models\PurchaseOrder' => [3],
            'App\Models\DeliveryOrder' => [7, 11],
            'App\Models\ProductionEntry' => [12],
            'App\Models\QcInspection' => [8, 17],
            'App\Models\CRM\Lead' => [6],
            'App\Models\CRM\Opportunity' => [6],
            'App\Models\CRM\Campaign' => [6],
            'App\Models\CRM\SalesVisit' => [2, 6],
            'App\Models\GoodsReceipt' => [3],
            'App\Models\PurchaseRequest' => [3],
            'App\Models\SalesInvoice' => [2],
            'App\Models\PurchaseInvoice' => [3],
        ];

        $logCount = 0;

        foreach ($mappings as $modelClass => $userIds) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $records = $modelClass::all();
            foreach ($records as $record) {
                $userId = $userIds[array_rand($userIds)];
                $date = $record->created_at ?? $record->order_date ?? $record->delivery_date ?? Carbon::now();

                // Create 'created' activity log
                Activity::create([
                    'log_name' => 'default',
                    'description' => 'created',
                    'subject_type' => $modelClass,
                    'subject_id' => $record->id,
                    'causer_type' => 'App\Models\User',
                    'causer_id' => $userId,
                    'properties' => json_encode(['attributes' => $record->toArray()]),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                $logCount++;

                // Randomly create an 'updated' log a few hours later for some records
                if (rand(1, 10) > 6) {
                    $updateDate = Carbon::parse($date)->addHours(rand(1, 8));
                    Activity::create([
                        'log_name' => 'default',
                        'description' => 'updated',
                        'subject_type' => $modelClass,
                        'subject_id' => $record->id,
                        'causer_type' => 'App\Models\User',
                        'causer_id' => $userId,
                        'properties' => json_encode(['old' => [], 'attributes' => []]),
                        'created_at' => $updateDate,
                        'updated_at' => $updateDate,
                    ]);
                    $logCount++;
                }
            }
        }

        $this->command->info("Successfully generated {$logCount} activity logs!");
    }
}
