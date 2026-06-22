<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Leave;
use App\Models\HR\LeaveBalance;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['employee.department', 'employee.position', 'leaveType'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $leaves = $query->paginate(15)->withQueryString();

        return Inertia::render('HR/Leaves/Index', [
            'leaves' => $leaves,
            'filters' => $request->only(['status'])
        ]);
    }

    public function approve(Leave $leave)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR', 'HR & Payroll']) && !$user->can('hr_payroll.leave_management.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $leaveType = $leave->leaveType;

        // Deduct balance
        if ($leaveType->is_paid) {
            $year = Carbon::parse($leave->start_date)->year;
            $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();

            if ($balance) {
                // Check again to be safe
                if ($balance->used_days + $leave->total_days > $balance->total_days) {
                     return back()->with('error', 'Approval failed: Insufficient leave balance.');
                }
                
                $balance->increment('used_days', $leave->total_days);
            } else {
                 return back()->with('error', 'Approval failed: Leave balance record not found.');
            }
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(Request $request, Leave $leave)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR', 'HR & Payroll']) && !$user->can('hr_payroll.leave_management.edit'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Leave request rejected.');
    }
}
