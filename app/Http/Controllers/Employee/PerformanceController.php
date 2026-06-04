<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\OkrObjective;
use App\Models\HR\OkrKeyResult;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->back()->with('error', 'You are not registered as an employee.');
        }

        $period = $request->query('period', date('Y') . '-Q' . ceil(date('n') / 3));

        $objectives = OkrObjective::with('keyResults')
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->get();

        return Inertia::render('Employee/Performance/Index', [
            'objectives' => $objectives,
            'currentPeriod' => $period,
            'employee' => $employee
        ]);
    }

    public function storeObjective(Request $request)
    {
        $employee = auth()->user()->employee;
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'period' => 'required|string',
            'weight' => 'required|integer|min:1|max:100',
        ]);

        $validated['employee_id'] = $employee->id;
        OkrObjective::create($validated);

        return redirect()->back()->with('success', 'OKR Objective created.');
    }

    public function storeKeyResult(Request $request, OkrObjective $objective)
    {
        // Ensure objective belongs to user
        if ($objective->employee_id !== auth()->user()->employee->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_value' => 'required|numeric|min:1',
        ]);

        $validated['objective_id'] = $objective->id;
        OkrKeyResult::create($validated);

        return redirect()->back()->with('success', 'Key Result added.');
    }

    public function updateKeyResult(Request $request, OkrKeyResult $keyResult)
    {
        if ($keyResult->objective->employee_id !== auth()->user()->employee->id) {
            abort(403);
        }

        $validated = $request->validate([
            'current_value' => 'required|numeric|min:0',
        ]);

        $keyResult->update($validated);

        return redirect()->back()->with('success', 'Progress updated.');
    }
}
