<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DriverRoleSeeder extends Seeder
{
    public function run(): void
    {
        $actions = ['view', 'create', 'edit', 'delete'];

        // ========================================
        // 1. Driver Module Permissions
        // ========================================
        $driverPerms = [];
        foreach ($actions as $action) {
            $driverPerms[] = "driver.$action";
            $driverPerms[] = "driver.dashboard.$action";
            $driverPerms[] = "driver.scan_qr.$action";
        }

        // ========================================
        // 2. Missing Logistics Sub-menus
        // ========================================
        $logisticsNewPerms = [];
        foreach (['logistics_hub', 'loading_queue', 'dispatch'] as $sub) {
            foreach ($actions as $action) {
                $logisticsNewPerms[] = "logistics.$sub.$action";
            }
        }

        // ========================================
        // 3. Missing Maintenance CRUD (only had .view)
        // ========================================
        $maintenancePerms = [];
        foreach (['schedule', 'breakdown', 'spareparts'] as $sub) {
            foreach ($actions as $action) {
                $maintenancePerms[] = "maintenance.$sub.$action";
            }
        }
        // Also add module-level CRUD
        foreach ($actions as $action) {
            $maintenancePerms[] = "maintenance.$action";
        }

        // ========================================
        // 4. Missing QC Sub-menus CRUD
        // ========================================
        $qcPerms = [];
        foreach (['ncr', 'coa', 'master_data', 'dashboard', 'checklists'] as $sub) {
            foreach ($actions as $action) {
                $qcPerms[] = "qc.$sub.$action";
            }
        }

        // ========================================
        // 5. Missing Finance Sub-menus
        // ========================================
        $financePerms = [];
        foreach (['production_costing', 'overhead_allocation'] as $sub) {
            foreach ($actions as $action) {
                $financePerms[] = "finance.$sub.$action";
            }
        }

        // ========================================
        // 6. Project Matrix (entirely missing)
        // ========================================
        $projectPerms = [];
        foreach ($actions as $action) {
            $projectPerms[] = "project_matrix.$action";
            $projectPerms[] = "project_matrix.projects.$action";
        }

        // ========================================
        // 7. Warehouse Module (for warehouse staff)
        // ========================================
        $warehousePerms = [];
        foreach ($actions as $action) {
            $warehousePerms[] = "warehouse.$action";
            $warehousePerms[] = "warehouse.loading_queue.$action";
        }

        // Merge all permissions
        $allNewPerms = array_merge(
            $driverPerms,
            $logisticsNewPerms,
            $maintenancePerms,
            $qcPerms,
            $financePerms,
            $projectPerms,
            $warehousePerms
        );

        // Create all permissions
        foreach ($allNewPerms as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ========================================
        // Create Driver Role & assign driver perms
        // ========================================
        $driverRole = Role::firstOrCreate([
            'name' => 'Driver',
            'guard_name' => 'web',
        ]);
        $driverRole->syncPermissions($driverPerms);

        // ========================================
        // Give Super Admin all new permissions
        // ========================================
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($allNewPerms);
        }
    }
}
