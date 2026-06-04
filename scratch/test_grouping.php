<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

$permissions = Permission::all();

// Group permissions for the UI
$groupedPermissions = [];

foreach ($permissions as $perm) {
    $parts = explode('.', $perm->name);
    $module = $parts[0];
    
    if (count($parts) === 3) {
        $menu = $parts[1];
        $action = $parts[2];
        
        $groupedPermissions[$module]['menus'][$menu]['actions'][] = $action;
        $groupedPermissions[$module]['menus'][$menu]['name'] = prettify($menu);
    } else if (count($parts) === 2) {
        $action = $parts[1];
        $groupedPermissions[$module]['actions'][] = $action;
    }
    
    $groupedPermissions[$module]['name'] = prettify($module);
}

function prettify($text) {
    $map = [
        'sales_crm' => 'Sales & CRM',
        'purchasing' => 'Purchasing',
        'inventory' => 'Inventory',
        'manufacturing' => 'Manufacturing',
        'qc' => 'Quality Control',
        'logistics' => 'Logistics',
        'finance' => 'Finance',
        'hr_payroll' => 'HR & Payroll',
        'general_affair' => 'General Affair',
        'meeting_command' => 'Meeting Command',
        'settings' => 'Settings'
    ];

    if (isset($map[$text])) return $map[$text];
    
    return Str::title(str_replace('_', ' ', $text));
}

echo "Grouped Permissions Keys:\n";
print_r(array_keys($groupedPermissions));

echo "\nDetails for general_affair:\n";
if (isset($groupedPermissions['general_affair'])) {
    print_r($groupedPermissions['general_affair']);
} else {
    echo "general_affair NOT FOUND\n";
}

echo "\nDetails for meeting_command:\n";
if (isset($groupedPermissions['meeting_command'])) {
    print_r($groupedPermissions['meeting_command']);
} else {
    echo "meeting_command NOT FOUND\n";
}
