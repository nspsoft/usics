<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveBalance;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default Leave Types
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'description' => 'Standard yearly paid leave (Cuti Tahunan)',
                'max_days' => 12,
                'is_paid' => true,
                'requires_attachment' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Leave for medical reasons (Cuti Sakit)',
                'max_days' => 14,
                'is_paid' => true,
                'requires_attachment' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Maternity leave for female employees (Cuti Melahirkan)',
                'max_days' => 90,
                'is_paid' => true,
                'requires_attachment' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'description' => 'Leave without pay (Cuti Di Luar Tanggungan)',
                'max_days' => 365,
                'is_paid' => false,
                'requires_attachment' => false,
                'is_active' => true,
            ]
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        // 2. Distribute Annual Leave Balance to All Existing Employees
        $annualLeave = LeaveType::where('name', 'Annual Leave')->first();
        $employees = Employee::all();
        $currentYear = Carbon::now()->year;

        if ($annualLeave && $employees->count() > 0) {
            foreach ($employees as $employee) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $annualLeave->id,
                        'year' => $currentYear,
                    ],
                    [
                        'total_days' => 12, // Default 12 days
                        'used_days' => 0,
                    ]
                );
            }
        }
    }
}
