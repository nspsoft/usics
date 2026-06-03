<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GaPmSchedule;
use App\Models\GaPmLog;
use App\Models\GaAsset;
use App\Models\User;
use Carbon\Carbon;

class GaPmController extends Controller
{
    public function index(Request $request)
    {
        $query = GaPmSchedule::with(['gaAsset', 'assignee']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('task_name', 'like', "%{$request->search}%")
                  ->orWhereHas('gaAsset', function($qa) use ($request) {
                      $qa->where('name', 'like', "%{$request->search}%")
                        ->orWhere('asset_code', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->status) {
            if ($request->status === 'overdue') {
                $query->where('status', 'active')
                      ->where('next_due_date', '<', Carbon::today());
            } else {
                $query->where('status', $request->status);
            }
        }

        $schedules = $query->orderBy('next_due_date', 'asc')->paginate(10)->withQueryString();
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Pm/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search', 'status']),
            'users' => $users,
        ]);
    }

    public function create()
    {
        $assets = GaAsset::where('status', 'active')->get(['id', 'name', 'asset_code']);
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Pm/Create', [
            'assets' => $assets,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ga_asset_id' => 'required|exists:ga_assets,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'interval_days' => 'required|integer|min:1',
            'next_due_date' => 'required|date',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:active,paused',
        ]);

        GaPmSchedule::create($validated);

        return redirect()->route('ga.pm-schedules.index')->with('success', 'Preventive Maintenance schedule created successfully.');
    }

    public function show($id)
    {
        $schedule = GaPmSchedule::with(['gaAsset.gaLocation', 'assignee', 'pmLogs.performedBy'])->findOrFail($id);

        return inertia('GeneralAffair/Pm/Show', [
            'schedule' => $schedule
        ]);
    }

    public function edit($id)
    {
        $schedule = GaPmSchedule::findOrFail($id);
        $assets = GaAsset::get(['id', 'name', 'asset_code']);
        $users = User::all(['id', 'name']);

        return inertia('GeneralAffair/Pm/Edit', [
            'schedule' => $schedule,
            'assets' => $assets,
            'users' => $users
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = GaPmSchedule::findOrFail($id);

        $validated = $request->validate([
            'ga_asset_id' => 'required|exists:ga_assets,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'interval_days' => 'required|integer|min:1',
            'next_due_date' => 'required|date',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:active,paused',
        ]);

        $schedule->update($validated);

        return redirect()->route('ga.pm-schedules.index')->with('success', 'Preventive Maintenance schedule updated successfully.');
    }

    public function destroy($id)
    {
        $schedule = GaPmSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('ga.pm-schedules.index')->with('success', 'Preventive Maintenance schedule deleted successfully.');
    }

    public function storeLog(Request $request, $id)
    {
        $schedule = GaPmSchedule::findOrFail($id);

        $validated = $request->validate([
            'performed_at' => 'required|date',
            'technician_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
        ]);

        // Create PM Executed Log
        $log = $schedule->pmLogs()->create([
            'performed_at' => $validated['performed_at'],
            'performed_by_id' => auth()->id(),
            'technician_name' => $validated['technician_name'],
            'notes' => $validated['notes'],
            'cost' => $validated['cost'],
        ]);

        // Update PM Schedule dates
        $schedule->last_performed_at = $validated['performed_at'];
        $schedule->next_due_date = Carbon::parse($validated['performed_at'])->addDays($schedule->interval_days);
        $schedule->save();

        // Log this event to GaAssetLog
        $formattedCost = $validated['cost'] ? 'Rp ' . number_format($validated['cost'], 0, ',', '.') : 'Rp 0';
        $schedule->gaAsset->logs()->create([
            'action' => 'maintenance',
            'notes' => "Preventive Maintenance: {$schedule->task_name} performed by " . ($validated['technician_name'] ?: auth()->user()->name) . ". Cost: {$formattedCost}. Notes: " . ($validated['notes'] ?: '-'),
            'user_id' => auth()->id()
        ]);

        return redirect()->route('ga.pm-schedules.index')->with('success', 'Maintenance service logged and next due date rescheduled successfully.');
    }
}
