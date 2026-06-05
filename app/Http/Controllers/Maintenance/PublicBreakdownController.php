<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PublicBreakdownController extends Controller
{
    /**
     * Show the public breakdown reporting form.
     */
    public function show($uuid)
    {
        $machine = Machine::where('qr_code_uuid', $uuid)->firstOrFail();

        return Inertia::render('Maintenance/PublicReport', [
            'machine' => [
                'id' => $machine->id,
                'name' => $machine->name,
                'code' => $machine->code ?? '-',
                'qr_code_uuid' => $machine->qr_code_uuid,
            ]
        ]);
    }

    /**
     * Submit the public breakdown report.
     */
    public function store(Request $request, $uuid)
    {
        $machine = Machine::where('qr_code_uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'reporter_name' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high',
        ]);

        // Create breakdown log
        $description = "[Tingkat: " . strtoupper($validated['severity']) . "] " . $validated['description'] . " (Pelapor: " . $validated['reporter_name'] . ")";

        MaintenanceLog::create([
            'machine_id' => $machine->id,
            'type' => 'breakdown',
            'description' => $description,
            'started_at' => Carbon::now(),
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', 'Kerusakan berhasil dilaporkan. Tim teknisi akan segera memeriksa mesin.');
    }
}
