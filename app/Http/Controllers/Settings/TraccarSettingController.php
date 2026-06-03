<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\TraccarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class TraccarSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/TraccarSettings', [
            'settings' => [
                'traccar_base_url' => AppSetting::get('traccar_base_url', ''),
                'traccar_username' => AppSetting::get('traccar_username', ''),
                'has_password' => AppSetting::get('traccar_password', '') !== '',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'traccar_base_url' => 'required|string|max:255',
            'traccar_username' => 'required|string|max:255',
            'traccar_password' => 'nullable|string|max:255',
        ]);

        AppSetting::set('traccar_base_url', rtrim($validated['traccar_base_url'], '/'), 'traccar', 'Traccar Base URL');
        AppSetting::set('traccar_username', $validated['traccar_username'], 'traccar', 'Traccar Username');

        if (($validated['traccar_password'] ?? '') !== '') {
            AppSetting::set('traccar_password', Crypt::encryptString($validated['traccar_password']), 'traccar', 'Traccar Password');
        }

        return redirect()->back()->with('success', 'Traccar configuration saved successfully!');
    }

    public function test(Request $request, TraccarService $traccar)
    {
        try {
            if (!$traccar->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi Traccar belum lengkap.',
                ], 400);
            }

            $devices = $traccar->getDevices();

            return response()->json([
                'success' => true,
                'message' => 'Koneksi ke Traccar berhasil.',
                'devices' => count($devices),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal konek Traccar: ' . $e->getMessage(),
            ], 500);
        }
    }
}

