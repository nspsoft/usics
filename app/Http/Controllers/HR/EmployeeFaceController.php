<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;

class EmployeeFaceController extends Controller
{
    public function show(Employee $employee)
    {
        return Inertia::render('HR/Employees/FaceRegistration', [
            'employee' => $employee
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $request->validate([
            'face_descriptor' => 'required|string',
        ]);

        $employee->update([
            'face_descriptor' => $request->face_descriptor,
        ]);

        return redirect()->route('hr.employees.show', $employee->id)->with('success', 'Face data registered successfully.');
    }
}
