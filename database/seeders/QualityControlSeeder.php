<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class QualityControlSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'qc.view',
            'qc.dashboard.view',
            'qc.incoming_inspection.view',
            'qc.in-process_qc.view',
            'qc.checklists.view', // Keeping for legacy/future
            'qc.ncr.view',
            'qc.coa.view',
            'qc.master_data.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Super Admin
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
        
        // Create Quality Control Role
        $qcRoleExists = Role::where('name', 'Quality Control Manager')->exists();
        $qcRole = Role::firstOrCreate(['name' => 'Quality Control Manager']);
        if (!$qcRoleExists) {
            $qcRole->givePermissionTo($permissions);
        }

        $inspectorRoleExists = Role::where('name', 'Quality Inspector')->exists();
        $inspectorRole = Role::firstOrCreate(['name' => 'Quality Inspector']);
        if (!$inspectorRoleExists) {
            $inspectorRole->givePermissionTo([
                'qc.view',
                'qc.dashboard.view',
                'qc.incoming_inspection.view',
                'qc.in-process_qc.view',
                'qc.ncr.view',
            ]);
        }
    }
}
