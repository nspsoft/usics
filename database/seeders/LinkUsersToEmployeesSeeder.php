<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;

class LinkUsersToEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Run basic HrSeeder if no departments exist yet
        if (Department::count() === 0) {
            $this->call(HrSeeder::class);
        }

        // 2. Define additional departments
        $additionalDepts = [
            ['code' => 'DEPT-PUR', 'name' => 'Purchasing'],
            ['code' => 'DEPT-MNT', 'name' => 'Maintenance'],
            ['code' => 'DEPT-INV', 'name' => 'Inventory & Warehouse'],
            ['code' => 'DEPT-LOG', 'name' => 'Logistics'],
        ];

        foreach ($additionalDepts as $deptData) {
            Department::firstOrCreate(
                ['code' => $deptData['code']],
                ['name' => $deptData['name'], 'is_active' => true]
            );
        }

        // Fetch all departments for reference
        $deptHR = Department::where('code', 'DEPT-HR')->first();
        $deptIT = Department::where('code', 'DEPT-IT')->first();
        $deptPRD = Department::where('code', 'DEPT-PRD')->first();
        $deptFIN = Department::where('code', 'DEPT-FIN')->first();
        $deptMKT = Department::where('code', 'DEPT-MKT')->first();
        $deptPUR = Department::where('code', 'DEPT-PUR')->first();
        $deptMNT = Department::where('code', 'DEPT-MNT')->first();
        $deptINV = Department::where('code', 'DEPT-INV')->first();
        $deptLOG = Department::where('code', 'DEPT-LOG')->first();

        // 3. Define additional positions
        $additionalPositions = [
            ['dept' => $deptIT, 'name' => 'IT Manager'],
            ['dept' => $deptMKT, 'name' => 'Sales Manager'],
            ['dept' => $deptPUR, 'name' => 'Purchasing Manager'],
            ['dept' => $deptMNT, 'name' => 'Maintenance Manager'],
            ['dept' => $deptINV, 'name' => 'Warehouse Manager'],
            ['dept' => $deptFIN, 'name' => 'Finance Manager'],
            ['dept' => $deptLOG, 'name' => 'Logistics Manager'],
        ];

        foreach ($additionalPositions as $posData) {
            if ($posData['dept']) {
                Position::firstOrCreate(
                    [
                        'department_id' => $posData['dept']->id,
                        'name' => $posData['name']
                    ],
                    [
                        'salary_range_min' => 10000000,
                        'salary_range_max' => 18000000,
                        'is_active' => true
                    ]
                );
            }
        }

        // 4. Map user accounts to employee records
        $mappings = [
            [
                'email' => 'admin@usc-indonesia.co.id',
                'nik' => 'EMP-ADMIN',
                'dept' => $deptIT,
                'pos_name' => 'IT Manager',
                'salary' => 15000000,
            ],
            [
                'email' => 'natasurya@usc-indonesia.co.id',
                'nik' => 'EMP-NATASURYA',
                'dept' => $deptMKT,
                'pos_name' => 'Sales Manager',
                'salary' => 14000000,
            ],
            [
                'email' => 'agus@usc-indonesia.co.id',
                'nik' => 'EMP-AGUS',
                'dept' => $deptPRD,
                'pos_name' => 'Production Manager',
                'salary' => 12000000,
            ],
            [
                'email' => 'santi@usc-indonesia.co.id',
                'nik' => 'EMP-SANTI',
                'dept' => $deptPUR,
                'pos_name' => 'Purchasing Manager',
                'salary' => 11000000,
            ],
            [
                'email' => 'andi@usc-indonesia.co.id',
                'nik' => 'EMP-ANDI',
                'dept' => $deptMNT,
                'pos_name' => 'Maintenance Manager',
                'salary' => 11000000,
            ],
            [
                'email' => 'amur@usc-indonesia.co.id',
                'nik' => 'EMP-AMUR',
                'dept' => $deptINV,
                'pos_name' => 'Warehouse Manager',
                'salary' => 11000000,
            ],
            [
                'email' => 'rustam@usc-indonesia.co.id',
                'nik' => 'EMP-RUSTAM',
                'dept' => $deptINV,
                'pos_name' => 'Warehouse Manager',
                'salary' => 11000000,
            ],
            [
                'email' => 'widya@usc-indonesia.co.id',
                'nik' => 'EMP-WIDYA',
                'dept' => $deptFIN,
                'pos_name' => 'Finance Manager',
                'salary' => 12000000,
            ],
            [
                'email' => 'delivery@usc-indonesia.co.id',
                'nik' => 'EMP-DELIVERY',
                'dept' => $deptLOG,
                'pos_name' => 'Logistics Manager',
                'salary' => 10000000,
            ],
            [
                'email' => 'produksi@usc-indonesia.co.id',
                'nik' => 'EMP-PRODUKSI',
                'dept' => $deptPRD,
                'pos_name' => 'Floor Operator',
                'salary' => 5000000,
            ],
        ];

        foreach ($mappings as $map) {
            $user = User::where('email', $map['email'])->first();
            if (!$user) {
                $this->command->warn("User with email {$map['email']} not found. Skipping.");
                continue;
            }

            if (!$map['dept']) {
                $this->command->error("Department for {$map['email']} not found. Skipping.");
                continue;
            }

            $position = Position::where('department_id', $map['dept']->id)
                ->where('name', $map['pos_name'])
                ->first();

            if (!$position) {
                $this->command->error("Position {$map['pos_name']} for department {$map['dept']->name} not found. Skipping.");
                continue;
            }

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $map['nik'],
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => '08123456789',
                    'address' => 'Office Address',
                    'department_id' => $map['dept']->id,
                    'position_id' => $position->id,
                    'joining_date' => '2024-01-01',
                    'employment_status' => 'permanent',
                    'basic_salary' => $map['salary'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Linked user '{$user->name}' to employee record (NIK: {$map['nik']}).");
        }
    }
}
