<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\HR\OkrObjective;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y') . '-Q' . ceil(date('n') / 3));

        $employees = Employee::with(['department', 'position'])->get()->map(function ($emp) use ($period) {
            $objectives = OkrObjective::where('employee_id', $emp->id)->where('period', $period)->get();
            $emp->total_okr_score = $objectives->isEmpty() ? 0 : round($objectives->avg('score'), 2);
            $emp->objectives_count = $objectives->count();
            return $emp;
        });

        return Inertia::render('HR/Performance/Index', [
            'employees' => $employees,
            'currentPeriod' => $period,
        ]);
    }

    public function show(Employee $employee, Request $request)
    {
        $period = $request->query('period', date('Y') . '-Q' . ceil(date('n') / 3));
        
        $objectives = OkrObjective::with('keyResults')
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->get();

        $employee->load('department', 'position');

        return Inertia::render('HR/Performance/Show', [
            'employee' => $employee,
            'objectives' => $objectives,
            'currentPeriod' => $period
        ]);
    }
}
