<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsappSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'whatsapp_provider' => AppSetting::get('whatsapp_provider', 'fonnte'),
            'fonnte_api_token' => AppSetting::get('fonnte_api_token', ''),
            'fonnte_device' => AppSetting::get('fonnte_device', ''),
            'wablas_api_token' => AppSetting::get('wablas_api_token', ''),
            'wablas_device' => AppSetting::get('wablas_device', ''),
            'wablas_server_url' => AppSetting::get('wablas_server_url', 'https://pati.wablas.com'),
        ];

        return Inertia::render('Settings/WhatsappSettings', [
            'settings' => $settings,
            'appUrl' => config('app.url'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'whatsapp_provider' => 'required|in:fonnte,wablas',
            'fonnte_api_token' => 'nullable|string|max:500',
            'fonnte_device' => 'nullable|string|max:20',
            'wablas_api_token' => 'nullable|string|max:500',
            'wablas_device' => 'nullable|string|max:20',
            'wablas_server_url' => 'nullable|url|max:255',
        ]);

        // Save provider selection
        AppSetting::set('whatsapp_provider', $request->whatsapp_provider, 'whatsapp', 'WhatsApp Provider');

        // Save Fonnte settings
        if ($request->fonnte_api_token) {
            AppSetting::set('fonnte_api_token', $request->fonnte_api_token, 'whatsapp', 'Fonnte API Token');
        }
        if ($request->fonnte_device) {
            AppSetting::set('fonnte_device', $request->fonnte_device, 'whatsapp', 'Fonnte Device ID');
        }

        // Save Wablas settings
        if ($request->wablas_api_token) {
            AppSetting::set('wablas_api_token', $request->wablas_api_token, 'whatsapp', 'Wablas API Token');
        }
        if ($request->wablas_device) {
            AppSetting::set('wablas_device', $request->wablas_device, 'whatsapp', 'Wablas Device ID');
        }
        if ($request->wablas_server_url) {
            AppSetting::set('wablas_server_url', $request->wablas_server_url, 'whatsapp', 'Wablas Server URL');
        }

        return redirect()->back()->with('success', 'WhatsApp configuration saved successfully!');
    }

    /**
     * Test connection by sending a test message
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $provider = AppSetting::get('whatsapp_provider', 'fonnte');
        $phone = $request->phone;

        $testMessage = "🔔 *JICOS ERP - Test Connection*\n\n";
        $testMessage .= "✅ Koneksi WhatsApp Bot berhasil!\n\n";
        $testMessage .= "📱 Provider: " . strtoupper($provider) . "\n";
        $testMessage .= "⏰ Waktu: " . now()->format('d M Y H:i:s') . "\n\n";
        $testMessage .= "_Pesan ini dikirim otomatis untuk testing._";

        try {
            // Use appropriate service based on provider
            if ($provider === 'wablas') {
                $service = app(WablasService::class);
            } else {
                $service = app(FonnteService::class);
            }

            $result = $service->sendMessage($phone, $testMessage);

            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan test berhasil dikirim ke ' . $phone,
                    'provider' => $provider,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim pesan: ' . ($result['error'] ?? 'Unknown error'),
                    'provider' => $provider,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'provider' => $provider,
            ], 500);
        }
    }
}

