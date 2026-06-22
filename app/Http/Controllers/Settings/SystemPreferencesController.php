<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemPreferencesController extends Controller
{
    /**
     * Display the system preferences page
     */
    public function index()
    {
        $settings = AppSetting::first() ?? new AppSetting();

        return Inertia::render('Settings/SystemPreferences', [
            'preferences' => [
                // UI/UX
                'default_theme' => AppSetting::get('default_theme', 'dark'),
                'sidebar_collapsed' => AppSetting::get('sidebar_collapsed', false),
                'items_per_page' => AppSetting::get('items_per_page', 25),
                
                // Inventory
                'auto_update_stock' => AppSetting::get('auto_update_stock', true),
                'allow_negative_stock' => AppSetting::get('allow_negative_stock', false),
                
                // Sales
                'require_po_number' => AppSetting::get('require_po_number', false),
                'default_payment_terms' => AppSetting::get('default_payment_terms', 'NET 30'),
                'auto_so_from_quotation' => AppSetting::get('auto_so_from_quotation', false),
                
                // Notifications
                'email_on_new_order' => AppSetting::get('email_on_new_order', true),
                'notify_low_stock' => AppSetting::get('notify_low_stock', true),
                
                // Security
                'session_timeout' => AppSetting::get('session_timeout', 120),

                // Smart Attendance
                'office_latitude' => $settings->office_latitude ?? '',
                'office_longitude' => $settings->office_longitude ?? '',
                'max_radius_meters' => $settings->max_radius_meters ?? 50,
            ],
        ]);
    }

    /**
     * Update preferences
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        // Extract and update the Smart Attendance columns directly on the first AppSetting row if present
        $appSettings = AppSetting::first() ?? new AppSetting();
        
        $hasOfficeCols = false;
        if (array_key_exists('office_latitude', $validated['preferences'])) {
            $appSettings->office_latitude = $validated['preferences']['office_latitude'];
            $hasOfficeCols = true;
        }
        if (array_key_exists('office_longitude', $validated['preferences'])) {
            $appSettings->office_longitude = $validated['preferences']['office_longitude'];
            $hasOfficeCols = true;
        }
        if (array_key_exists('max_radius_meters', $validated['preferences'])) {
            $appSettings->max_radius_meters = $validated['preferences']['max_radius_meters'];
            $hasOfficeCols = true;
        }
        
        if ($hasOfficeCols) {
            // If it's a new model, we might need a default key/value to save it successfully if key is required
            if (!$appSettings->exists) {
                $appSettings->key = 'office_location';
                $appSettings->value = ['value' => 'office_location'];
                $appSettings->group = 'system';
            }
            $appSettings->save();
        }

        foreach ($validated['preferences'] as $key => $value) {
            // Do not save columns as key-value rows
            if (in_array($key, ['office_latitude', 'office_longitude', 'max_radius_meters'])) {
                continue;
            }
            AppSetting::set($key, $value, 'system', ucwords(str_replace('_', ' ', $key)));
        }

        return back()->with('success', 'Preferences saved successfully.');
    }
}
