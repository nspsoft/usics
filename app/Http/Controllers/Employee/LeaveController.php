<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Leave;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;
        
        $year = Carbon::now()->year;

        $balances = $employee ? LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get() : collect([]);

        $leaves = $employee ? Leave::with('leaveType')
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get() : collect([]);

        // Calculate pending vs approved
        $stats = [
            'pending' => $leaves->where('status', 'pending')->count(),
            'approved' => $leaves->where('status', 'approved')->count(),
        ];

        return Inertia::render('Employee/Leaves/Dashboard', [
            'balances' => $balances,
            'leaves' => $leaves,
            'stats' => $stats,
            'is_employee' => $employee ? true : false,
        ]);
    }

    public function create()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('my-timeoff.index')->with('error', 'You are not registered as an employee, so you cannot request time-off.');
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
            return redirect()->back()->with('error', 'Unauthorized.');
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
}
