<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class MobileNavbarController extends Controller
{
    public function index()
    {
        $config = AppSetting::get('mobile_navbar_config', []);

        if (!is_array($config)) {
            $config = [];
        }

        $config = array_merge([
            'enabled' => false,
            'nav_by_role' => [],
            'quick_by_role' => [],
        ], $config);

        return Inertia::render('Settings/MobileNavbar', [
            'roles' => Role::query()->orderBy('name')->pluck('name')->values(),
            'config' => $config,
            'navCatalog' => $this->navCatalog(),
            'quickCatalog' => $this->quickCatalog(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'nav_by_role' => ['nullable', 'array'],
            'quick_by_role' => ['nullable', 'array'],
        ]);

        $navCatalog = $this->navCatalog();
        $quickCatalog = $this->quickCatalog();

        $allowedNavKeys = array_values(array_map(fn ($x) => $x['key'], $navCatalog));
        $allowedQuickKeys = array_values(array_map(fn ($x) => $x['key'], $quickCatalog));

        $navByRole = $validated['nav_by_role'] ?? [];
        $quickByRole = $validated['quick_by_role'] ?? [];

        foreach ($navByRole as $roleName => $keys) {
            if (!is_array($keys)) {
                return back()->with('error', "Invalid navbar config for role: {$roleName}");
            }

            $keys = array_values(array_filter($keys, fn ($v) => is_string($v) && $v !== ''));

            if (count($keys) !== 5) {
                return back()->with('error', "Navbar untuk role {$roleName} harus tepat 5 menu.");
            }

            if (count(array_unique($keys)) !== count($keys)) {
                return back()->with('error', "Navbar untuk role {$roleName} tidak boleh duplikat.");
            }

            foreach ($keys as $k) {
                if (!in_array($k, $allowedNavKeys, true)) {
                    return back()->with('error', "Navbar key tidak valid untuk role {$roleName}: {$k}");
                }
            }

            $navByRole[$roleName] = $keys;
        }

        foreach ($quickByRole as $roleName => $keys) {
            if (!is_array($keys)) {
                return back()->with('error', "Invalid quick actions config for role: {$roleName}");
            }

            $keys = array_values(array_filter($keys, fn ($v) => is_string($v) && $v !== ''));

            if (count($keys) > 6) {
                return back()->with('error', "Quick actions untuk role {$roleName} maksimal 6 item.");
            }

            if (count(array_unique($keys)) !== count($keys)) {
                return back()->with('error', "Quick actions untuk role {$roleName} tidak boleh duplikat.");
            }

            foreach ($keys as $k) {
                if (!in_array($k, $allowedQuickKeys, true)) {
                    return back()->with('error', "Quick action key tidak valid untuk role {$roleName}: {$k}");
                }
            }

            $quickByRole[$roleName] = $keys;
        }

        AppSetting::set('mobile_navbar_config', [
            'enabled' => (bool) $validated['enabled'],
            'nav_by_role' => $navByRole,
            'quick_by_role' => $quickByRole,
        ], 'system', 'Mobile Navbar Config');

        return back()->with('success', 'Mobile navbar settings saved.');
    }

    private function navCatalog(): array
    {
        return [
            ['key' => 'home', 'name' => 'Home'],
            ['key' => 'menu', 'name' => 'Menu'],
            ['key' => 'sales', 'name' => 'Sales'],
            ['key' => 'purchasing', 'name' => 'Purchasing'],
            ['key' => 'inventory', 'name' => 'Inventory'],
            ['key' => 'finance', 'name' => 'Finance'],
            ['key' => 'logistics', 'name' => 'Logistics'],
            ['key' => 'reports', 'name' => 'Reports'],
            ['key' => 'map', 'name' => 'Map'],
            ['key' => 'activity', 'name' => 'Activity'],
            ['key' => 'quick', 'name' => 'Create (Quick)'],
            ['key' => 'profile', 'name' => 'Profile'],
        ];
    }

    private function quickCatalog(): array
    {
        return [
            ['key' => 'pr', 'name' => 'New PR'],
            ['key' => 'po', 'name' => 'New PO'],
            ['key' => 'grn', 'name' => 'New GRN'],
            ['key' => 'pi', 'name' => 'New Invoice'],
            ['key' => 'quotation', 'name' => 'New Quotation'],
            ['key' => 'so', 'name' => 'New Sales Order'],
            ['key' => 'do', 'name' => 'New Delivery'],
            ['key' => 'movement', 'name' => 'New Movement'],
            ['key' => 'transfer', 'name' => 'New Transfer'],
            ['key' => 'adjustment', 'name' => 'New Adjustment'],
        ];
    }
}

