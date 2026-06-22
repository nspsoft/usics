<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $structure = [
            'Sales & CRM' => [
                'Sales Hub', 'Planning', 'Forecast', 'Schedule',
                'Customers', 'Quotations', 'Sales Orders', 'Delivery Orders', 'Invoices', 'Sales Returns',
                'Items Report', 'Information', 'PO Tracking',
                'WhatsApp Center', 'AI Email Inbox', 'CRM Intelligence',
                'Leads Management', 'Opportunity Tracking', 'Marketing Campaigns', 'AI PO Extractor'
            ],
            'Purchasing' => [
                'Suppliers', 'Purchase Requests', 'Purchase Orders', 'Goods Receipts', 'Purchase Invoices', 'Purchase Returns'
            ],
            'Inventory' => [
                'Categories', 'Products', 'Current Stock', 'Warehouses', 'Stock Movements', 'Stock Opname'
            ],
            'Manufacturing' => [
                'Bill of Materials', 'Work Orders', 'Production', 'Input Output', 'Shift Management', 'Machine Management', 'Subcontract Orders'
            ],
            'Maintenance' => [
                'Schedule', 'Breakdown', 'Spareparts'
            ],
            'QC' => [
                'Incoming Inspection', 'In-Process QC', 'Quality Checklists'
            ],
            'Logistics' => [
                'Delivery Planning', 'Vehicle Fleet'
            ],
            'Finance' => [
                'General Ledger', 'Profit & Loss', 'AP & AR Monitoring', 'Production Costing', 'Overhead Allocation', 'Profitability Analytic'
            ],
            'HR & Payroll' => [
                'Employee Directory', 'Attendance', 'Leave Management',
                'Overtime', 'Reimbursements', 'Payroll', 'Performance', 'Recruitment'
            ],
            'General Affair' => [
                'Dashboard', 'Tickets', 'Assets', 'Preventive Maintenance',
                'Armada Operasional', 'Peminjaman Kendaraan', 'Locations', 'Requests PR'
            ],
            'Meeting Command' => [
                'Dashboard List', 'New Meeting'
            ],
            'Project Matrix' => [
                'Projects'
            ],
            'Settings' => [
                'User Management', 'Roles & Permissions', 'Company Profile', 'AI Configuration', 'Document Numbering', 'Regional & Tax', 'System Preferences', 'Workflow Approval', 'Mobile Navbar', 'Import & Export', 'Database Management', 'Activity Logs'
            ]
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($structure as $module => $menus) {
            $moduleKey = $this->slugify($module);
            
            // Add a general module access permission
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$moduleKey}.{$action}"]);
            }

            // Add granular menu access permissions
            foreach ($menus as $menu) {
                $menuKey = $this->slugify($menu);
                foreach ($actions as $action) {
                    Permission::firstOrCreate(['name' => "{$moduleKey}.{$menuKey}.{$action}"]);
                }
            }
        }

        $roles = [
            'Super Admin',
            'IT Administrator',
            'Sales Manager',
            'Marketing Manager',
            'Purchasing Manager',
            'Production Manager',
            'Maintenance Manager',
            'Warehouse Manager',
            'Logistics Manager',
            'Quality Control Manager',
            'Finance Manager',
            'HR & Payroll',
            'Director',
            'Production Operator',
            'Supplier'
        ];

        foreach ($roles as $roleName) {
            $roleExists = Role::where('name', $roleName)->exists();
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            if ($roleName === 'Super Admin') {
                $role->syncPermissions(Permission::all());
                continue;
            }

            if ($roleExists) {
                // Skip syncing default permissions for roles that already exist to preserve custom configuration
                continue;
            }

            // Granular Permissions
            $permissions = [];

            if ($roleName === 'IT Administrator') {
                $permissions = Permission::where('name', 'like', 'settings.%')->get();
            } 
            elseif ($roleName === 'Director') {
                $permissions = Permission::where('name', 'like', '%.view')->get();
            }
            elseif ($roleName === 'Sales Manager') {
                $permissions = Permission::where('name', 'like', 'sales_crm.%')->get();
            }
            elseif ($roleName === 'Marketing Manager') {
                $permissions = Permission::where('name', 'like', 'sales_crm.leads_management.%')
                    ->orWhere('name', 'like', 'sales_crm.opportunity_tracking.%')
                    ->orWhere('name', 'like', 'sales_crm.marketing_campaigns.%')
                    ->get();
            }
            elseif ($roleName === 'Purchasing Manager') {
                $permissions = Permission::where('name', 'like', 'purchasing.%')->get();
            }
            elseif ($roleName === 'Production Manager') {
                $permissions = Permission::where('name', 'like', 'manufacturing.%')->get();
            }
            elseif ($roleName === 'Maintenance Manager') {
                $permissions = Permission::where('name', 'like', 'maintenance.%')->get();
            }
            elseif ($roleName === 'Warehouse Manager') {
                $permissions = Permission::where('name', 'like', 'inventory.%')->get();
            }
            elseif ($roleName === 'Logistics Manager') {
                $permissions = Permission::where('name', 'like', 'logistics.%')->get();
            }
            elseif ($roleName === 'Quality Control Manager') {
                $permissions = Permission::where('name', 'like', 'qc.%')->get();
            }
            elseif ($roleName === 'Finance Manager') {
                $permissions = Permission::where('name', 'like', 'finance.%')->get();
            }
            elseif ($roleName === 'HR & Payroll') {
                $permissions = Permission::where('name', 'like', 'hr_payroll.%')
                    ->orWhere('name', 'like', 'general_affair.%')
                    ->get();
            }
            elseif ($roleName === 'Production Operator') {
                $permissions = Permission::whereIn('name', [
                    'manufacturing.work_orders.view',
                    'manufacturing.production.view',
                    'manufacturing.production.create',
                    'manufacturing.input_output.create'
                ])->get();
            }

            if (count($permissions) > 0) {
                $role->syncPermissions($permissions);
            }
        }

        // Assign Super Admin to potential admins
        $users = User::whereIn('email', ['test@example.com', 'admin@jicos.com', 'admin@jidoka.co.id'])->get();
        foreach ($users as $user) {
            if (!$user->hasRole('Super Admin')) {
                $user->assignRole('Super Admin');
            }
        }
    }

    private function slugify($text)
    {
        return strtolower(str_replace([' & ', ' '], ['_', '_'], $text));
    }
}
