<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\TraccarService;
use Inertia\Inertia;

class FleetTrackingController extends Controller
{
    public function index(TraccarService $traccar)
    {
        $vehicles = Vehicle::query()
            ->whereNotNull('traccar_device_id')
            ->orderBy('license_plate')
            ->get(['id', 'license_plate', 'driver_name', 'status', 'traccar_device_id']);

        return Inertia::render('Logistics/Tracking/Index', [
            'vehicles' => $vehicles,
            'traccarConfigured' => $traccar->isConfigured(),
        ]);
    }
}

