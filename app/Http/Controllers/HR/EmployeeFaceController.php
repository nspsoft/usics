<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;

use Illuminate\Support\Facades\Storage;

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
            'face_photo' => 'nullable|string',
        ]);

        if ($request->face_photo) {
            // Decode base64 image
            $image_parts = explode(";base64,", $request->face_photo);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $filename = 'employees/face_' . $employee->id . '_' . time() . '.jpg';
                
                // Store in public disk
                Storage::disk('public')->put($filename, $image_base64);
                
                // Delete old profile picture if exists
                if ($employee->profile_picture) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }
                
                // Update profile_picture path
                $employee->profile_picture = $filename;
            }
        }

        $employee->face_descriptor = $request->face_descriptor;
        $employee->save();

        return redirect()->route('hr.employees.index')->with('success', 'Face data registered successfully for ' . $employee->full_name . '.');
    }
}
