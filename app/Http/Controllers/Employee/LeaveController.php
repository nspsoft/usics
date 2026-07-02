<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Leave;
use App\Models\HR\AttendanceRequest;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak terdaftar sebagai Karyawan (Employee). Silakan hubungi HR atau Administrator untuk mendaftarkan NIK dan menautkan akun Anda.');
        }

        $year = Carbon::now()->year;

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        $leaves = Leave::with('leaveType')
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $attendanceRequests = AttendanceRequest::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Calculate pending vs approved
        $stats = [
            'pending' => $leaves->where('status', 'pending')->count(),
            'approved' => $leaves->where('status', 'approved')->count(),
        ];

        return Inertia::render('Employee/Leaves/Dashboard', [
            'balances' => $balances,
            'leaves' => $leaves,
            'attendanceRequests' => $attendanceRequests,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak terdaftar sebagai Karyawan (Employee). Silakan hubungi HR atau Administrator untuk mendaftarkan NIK dan menautkan akun Anda.');
        }

        $leaveTypes = LeaveType::where('is_active', true)->get();
        
        return Inertia::render('Employee/Leaves/Create', [
            'leaveTypes' => $leaveTypes
        ]);
    }

    public function store(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->back()->with('error', 'Akun Anda tidak terdaftar sebagai Karyawan (Employee). Silakan hubungi HR atau Administrator untuk mendaftarkan NIK dan menautkan akun Anda.');
        }

        $request->validate([
            'leave_type_id' => 'required|exists:hr_leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        
        if ($leaveType->requires_attachment && !$request->hasFile('attachment')) {
            return back()->withErrors(['attachment' => 'Attachment is required for this leave type.']);
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        // Simple days calculation (inclusive)
        $totalDays = $start->diffInDays($end) + 1;

        // Check balance if unpaid is false
        if ($leaveType->is_paid) {
            $year = Carbon::now()->year;
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                return back()->withErrors(['leave_type_id' => 'You do not have balance for this leave type.']);
            }

            $available = $balance->total_days - $balance->used_days;
            if ($totalDays > $available) {
                return back()->withErrors(['end_date' => "Requested days ($totalDays) exceeds available balance ($available)."]);
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = 'leave_' . $employee->id . '_' . time() . '.' . $file->extension();
            $attachmentPath = $file->storeAs('leave_attachments', $filename, 'public');
        }

        Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return redirect()->route('my-timeoff.index')->with('success', 'Leave request submitted successfully.');
    }

    public function checkPeersOnLeave(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) return response()->json([]);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();

        $peersOnLeave = Leave::with('employee')
            ->whereHas('employee', function($q) use ($employee) {
                $q->where('department_id', $employee->department_id)
                  ->where('id', '!=', $employee->id);
            })
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })
            ->whereIn('approval_status', ['pending', 'approved'])
            ->get();

        return response()->json($peersOnLeave);
    }
}
