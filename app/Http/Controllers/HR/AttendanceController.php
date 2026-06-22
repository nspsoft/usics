<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use App\Models\HR\AttendanceRequest;

use App\Imports\AttendanceImport;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Attendance::with(['employee.department', 'employee.position'])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        if ($request->search) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }

        if ($request->date) {
            $query->where('date', $request->date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requestsQuery = AttendanceRequest::with(['employee.department'])
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $requestsQuery->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('HR/Attendance/Index', [
            'attendances' => $query->paginate(15)->withQueryString(),
            'attendanceRequests' => $requestsQuery->paginate(10, ['*'], 'requests_page')->withQueryString(),
            'departments' => Department::all(),
            'filters' => $request->only(['search', 'date', 'status']),
        ]);
    }

    public function template()
    {
        return Excel::download(new AttendanceTemplateExport, 'attendance-template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new AttendanceImport, $request->file('file'));
            return redirect()->back()->with('success', 'Attendance data imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing attendance: ' . $e->getMessage());
        }
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ]);

        $today = Carbon::today()->toDateString();
        
        // Check if already clocked in today
        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            return redirect()->back()->with('error', 'Employee already clocked in for today.');
        }

        $now = Carbon::now();
        $status = 'present';
        
        // Simple logic: Late if after 08:30
        if ($now->format('H:i') > '08:30') {
            $status = 'late';
        }

        Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $today,
            'clock_in' => $now,
            'status' => $status,
            'location_lat' => $request->lat,
            'location_lng' => $request->lng,
        ]);

        return redirect()->back()->with('success', 'Clock-in recorded successfully.');
    }

    public function clockOut(Request $request, Attendance $attendance)
    {
        if ($attendance->clock_out) {
            return redirect()->back()->with('error', 'Employee already clocked out.');
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Clock-out recorded successfully.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'HR & Payroll', 'IT Administrator']) && !$user->can('hr_payroll.attendance.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'clock_in' => 'nullable|string',
            'clock_out' => 'nullable|string',
            'status' => 'required|in:present,late,absent,leave,sick,overtime',
            'note' => 'nullable|string',
        ]);

        $dateStr = Carbon::parse($validated['date'])->toDateString();

        $clockIn = null;
        if (!empty($validated['clock_in'])) {
            // Check if full datetime or just time
            if (strlen($validated['clock_in']) <= 8) {
                $clockIn = Carbon::parse($dateStr . ' ' . $validated['clock_in']);
            } else {
                $clockIn = Carbon::parse($validated['clock_in']);
            }
        }

        $clockOut = null;
        if (!empty($validated['clock_out'])) {
            if (strlen($validated['clock_out']) <= 8) {
                $clockOut = Carbon::parse($dateStr . ' ' . $validated['clock_out']);
            } else {
                $clockOut = Carbon::parse($validated['clock_out']);
            }
        }

        $attendance->update([
            'date' => $dateStr,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => $validated['status'],
            'note' => $validated['note'],
        ]);

        return redirect()->back()->with('success', 'Attendance record updated successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'HR & Payroll', 'IT Administrator']) && !$user->can('hr_payroll.attendance.delete'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $attendance->delete();
        return redirect()->back()->with('success', 'Attendance record deleted successfully.');
    }
}
