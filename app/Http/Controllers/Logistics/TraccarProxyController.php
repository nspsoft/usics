<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Services\TraccarService;
use Illuminate\Http\Request;

class TraccarProxyController extends Controller
{
    public function devices(Request $request, TraccarService $traccar)
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
                'data' => $devices,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function positions(Request $request, TraccarService $traccar)
    {
        try {
            if (!$traccar->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi Traccar belum lengkap.',
                ], 400);
            }

            $positions = $traccar->getPositions();

            return response()->json([
                'success' => true,
                'data' => $positions,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

