<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DriverRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Driver permissions
        $permissions = [
            'driver.view',
            'driver.create',
            'driver.edit',
            'driver.delete',
            'driver.dashboard.view',
            'driver.dashboard.create',
            'driver.dashboard.edit',
            'driver.dashboard.delete',
            'driver.scan_qr.view',
            'driver.scan_qr.create',
            'driver.scan_qr.edit',
            'driver.scan_qr.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create Driver role and assign permissions
        $role = Role::firstOrCreate([
            'name' => 'Driver',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);
    }
}
