<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::all();
        
        // Group permissions for the UI
        $groupedPermissions = [];
        
        foreach ($permissions as $perm) {
            $parts = explode('.', $perm->name);
            $module = $parts[0];
            
            if (count($parts) === 3) {
                // Granular: module.submenu.action
                $menu = $parts[1];
                $action = $parts[2];
                
                $groupedPermissions[$module]['menus'][$menu]['actions'][] = $action;
                $groupedPermissions[$module]['menus'][$menu]['name'] = $this->prettify($menu);
            } else if (count($parts) === 2) {
                // Module level: module.action
                $action = $parts[1];
                $groupedPermissions[$module]['actions'][] = $action;
            }
            
            $groupedPermissions[$module]['name'] = $this->prettify($module);
        }

        return Inertia::render('Settings/RolesPermissions', [
            'roles' => Role::with('permissions')->get(),
            'permissionStructure' => $groupedPermissions,
            'availableActions' => ['view', 'create', 'edit', 'delete']
        ]);
    }

    private function prettify($text)
    {
        $map = [
            'sales_crm' => 'Sales & CRM',
            'purchasing' => 'Purchasing',
            'inventory' => 'Inventory',
            'manufacturing' => 'Manufacturing',
            'qc' => 'Quality Control',
            'logistics' => 'Logistics',
            'finance' => 'Finance',
            'hr_payroll' => 'HR & Payroll',
            'maintenance' => 'Maintenance',
            'general_affair' => 'General Affair',
            'meeting_command' => 'Meeting Command',
            'settings' => 'Settings'
        ];

        if (isset($map[$text])) return $map[$text];
        
        return Str::title(str_replace('_', ' ', $text));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return redirect()->back()->with('error', 'Cannot delete Super Admin role.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
