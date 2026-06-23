<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::with(['department', 'position', 'user'])
            ->orderBy('full_name');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->status) {
            $query->where('employment_status', $request->status);
        }

        return Inertia::render('HR/Employees/Index', [
            'employees' => $query->paginate(20)->withQueryString(),
            'departments' => Department::all(),
            'positions' => Position::all(),
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $request->only(['search', 'department_id', 'status']),
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new EmployeeExport($request->department_id, $request->status),
            'employees-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(
            new EmployeeExport(null, null),
            'employee-template.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'overwrite' => 'boolean'
        ]);

        try {
            Excel::import(new EmployeeImport($request->overwrite), $request->file('file'));
            return redirect()->back()->with('success', 'Employees data imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|unique:hr_employees,nik',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'department_id' => 'required|exists:hr_departments,id',
            'position_id' => 'required|exists:hr_positions,id',
            'joining_date' => 'required|date',
            'employment_status' => 'required|in:permanent,contract,probation,internship',
            'basic_salary' => 'required|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
            'create_user' => 'nullable|boolean',
            'profile_picture' => 'nullable|image|max:2048', // max 2MB
        ]);

        if (empty($validated['user_id']) && !empty($validated['email'])) {
            $user = \App\Models\User::where('email', $validated['email'])->first();
            if ($user) {
                $validated['user_id'] = $user->id;
            } elseif (!empty($request->create_user)) {
                $user = \App\Models\User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['nik']),
                ]);
                $validated['user_id'] = $user->id;
            }
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('employees', 'public');
            $validated['profile_picture'] = $path;
        }

        Employee::create($validated);

        return redirect()->back()->with('success', 'Employee hired successfully.');
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'nik' => 'required|string|unique:hr_employees,nik,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'department_id' => 'required|exists:hr_departments,id',
            'position_id' => 'required|exists:hr_positions,id',
            'joining_date' => 'required|date',
            'employment_status' => 'required|in:permanent,contract,probation,internship',
            'basic_salary' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
            'user_id' => 'nullable|exists:users,id',
            'create_user' => 'nullable|boolean',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if (empty($validated['user_id']) && !empty($validated['email'])) {
            $user = \App\Models\User::where('email', $validated['email'])->first();
            if ($user) {
                $validated['user_id'] = $user->id;
            } elseif (!empty($request->create_user)) {
                $user = \App\Models\User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['nik']),
                ]);
                $validated['user_id'] = $user->id;
            }
        }

        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($employee->profile_picture) {
                Storage::disk('public')->delete($employee->profile_picture);
            }
            $path = $request->file('profile_picture')->store('employees', 'public');
            $validated['profile_picture'] = $path;
        }

        $employee->update($validated);

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->back()->with('success', 'Employee record deleted.');
    }
}
