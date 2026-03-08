<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\AttendanceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceRequestController extends Controller
{
    /**
     * Approve the specified attendance request.
     */
    public function approve(Request $request, AttendanceRequest $attendanceRequest)
    {
        // Must be pending
        if ($attendanceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Status pengajuan sudah tidak pending.');
        }

        $attendanceRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Here we could implement the actual modification to the employee's timesheet.
        // For MVP, we will just record the approval.

        return redirect()->back()->with('success', 'Izin kehadiran disetujui.');
    }

    /**
     * Reject the specified attendance request.
     */
    public function reject(Request $request, AttendanceRequest $attendanceRequest)
    {
        // Must be pending
        if ($attendanceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Status pengajuan sudah tidak pending.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $attendanceRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->back()->with('success', 'Izin kehadiran ditolak.');
    }

    /**
     * Delete the specified request (optional utility).
     */
    public function destroy(AttendanceRequest $attendanceRequest)
    {
        $attendanceRequest->delete();
        return redirect()->back()->with('success', 'Data pengajuan izin dihapus.');
    }
}
