<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\HR\OvertimeRequest;
use Carbon\Carbon;

class OvertimeTrialSeeder extends Seeder
{
    public function run(): void
    {
        echo "Seeding Overtime Request dummy data with auto-attendance...\n";

        // Get some employees
        $employees = Employee::take(3)->get();

        if ($employees->isEmpty()) {
            echo "No employees found in the database. Seeding skipped.\n";
            return;
        }

        foreach ($employees as $employee) {
            echo "Seeding overtime and attendance for employee: {$employee->full_name}\n";

            $today = Carbon::today();

            // 1. Pre-Planned Overtime (Status: Approved, Requested: 60 mins, Actual: 90 mins)
            $date1 = (clone $today)->subDays(3);
            $clockIn1 = (clone $date1)->setHour(8)->setMinute(0);
            $clockOut1 = (clone $date1)->setHour(18)->setMinute(30); // 90 mins overtime past 17:00

            // Delete existing attendance for this date to avoid duplicate key
            Attendance::where('employee_id', $employee->id)->whereDate('date', $date1)->delete();

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date1->toDateString(),
                'clock_in' => $clockIn1,
                'clock_out' => $clockOut1,
                'status' => 'present'
            ]);

            OvertimeRequest::create([
                'employee_id' => $employee->id,
                'type' => 'pre_planned',
                'date' => $date1->toDateString(),
                'start_time' => '17:00',
                'end_time' => '18:00',
                'requested_minutes' => 60,
                'approved_minutes' => 60, // reconciled: min(60, 90) = 60
                'reason' => 'Menyelesaikan laporan bulanan departemen.',
                'status' => 'approved',
                'approved_by' => 1, // Admin / Manager
                'approved_at' => now(),
            ]);

            // 2. Pre-Planned Overtime (Status: Approved, Requested: 120 mins, Actual: 90 mins)
            $date2 = (clone $today)->subDays(2);
            $clockIn2 = (clone $date2)->setHour(8)->setMinute(0);
            $clockOut2 = (clone $date2)->setHour(18)->setMinute(30); // 90 mins overtime past 17:00

            Attendance::where('employee_id', $employee->id)->whereDate('date', $date2)->delete();

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date2->toDateString(),
                'clock_in' => $clockIn2,
                'clock_out' => $clockOut2,
                'status' => 'present'
            ]);

            OvertimeRequest::create([
                'employee_id' => $employee->id,
                'type' => 'pre_planned',
                'date' => $date2->toDateString(),
                'start_time' => '17:00',
                'end_time' => '19:00',
                'requested_minutes' => 120,
                'approved_minutes' => 90, // reconciled: min(120, 90) = 90
                'reason' => 'Perbaikan bug kritis pada modul transaksi.',
                'status' => 'approved',
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 3. Post-Claim Overtime (Status: Pending, Requested: 90 mins, Actual: 90 mins)
            $date3 = (clone $today)->subDays(1);
            $clockIn3 = (clone $date3)->setHour(8)->setMinute(0);
            $clockOut3 = (clone $date3)->setHour(18)->setMinute(30); // 90 mins overtime past 17:00

            Attendance::where('employee_id', $employee->id)->whereDate('date', $date3)->delete();

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date3->toDateString(),
                'clock_in' => $clockIn3,
                'clock_out' => $clockOut3,
                'status' => 'present'
            ]);

            OvertimeRequest::create([
                'employee_id' => $employee->id,
                'type' => 'post_claim',
                'date' => $date3->toDateString(),
                'start_time' => '17:00',
                'end_time' => '18:30',
                'requested_minutes' => 90,
                'approved_minutes' => 0, // not approved yet
                'reason' => 'Meeting mendadak dengan klien luar negeri.',
                'status' => 'pending',
            ]);
        }

        echo "Overtime Seeding Completed!\n";
    }
}
