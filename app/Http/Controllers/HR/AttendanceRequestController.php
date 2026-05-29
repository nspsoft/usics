<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\HR\AttendanceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceRequestController extends Controller
{
    /**
     * Approve the specified attendance request.
     */
    public function approve(Request $request, AttendanceRequest $attendanceRequest)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR']) && !$user->can('hr.attendance.approve'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        // Must be pending
        if ($attendanceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Status pengajuan sudah tidak pending.');
        }

        try {
            DB::transaction(function () use ($attendanceRequest) {
                $date = Carbon::parse($attendanceRequest->request_date)->toDateString();
                $time = (string) $attendanceRequest->request_time;
                $time = strlen($time) === 5 ? $time . ':00' : $time;
                $requestedAt = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time);

                $attendance = Attendance::firstOrNew([
                    'employee_id' => $attendanceRequest->employee_id,
                    'date' => $date,
                ]);

                if ($attendanceRequest->type === 'early_dismissal') {
                    if ($attendance->clock_in && $requestedAt->lessThan(Carbon::parse($attendance->clock_in))) {
                        throw new \RuntimeException('Jam pulang tidak boleh lebih awal dari jam masuk.');
                    }

                    $attendance->clock_out = $requestedAt;
                    $attendance->status = $attendance->status ?: 'present';
                    $attendance->early_leave_minutes = 0;
                } else {
                    if ($attendance->clock_out && $requestedAt->greaterThan(Carbon::parse($attendance->clock_out))) {
                        throw new \RuntimeException('Jam masuk tidak boleh lebih lambat dari jam pulang.');
                    }

                    $attendance->clock_in = $requestedAt;
                    $attendance->status = 'present';
                    $attendance->late_minutes = 0;
                }

                $note = trim((string) $attendance->note);
                $line = 'Approved request: ' . $attendanceRequest->type . ' - ' . trim((string) $attendanceRequest->reason);
                $attendance->note = $note ? ($note . "\n" . $line) : $line;

                $attendance->save();

                $attendanceRequest->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Izin kehadiran disetujui.');
    }

    /**
     * Reject the specified attendance request.
     */
    public function reject(Request $request, AttendanceRequest $attendanceRequest)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Administrator', 'HR']) && !$user->can('hr.attendance.approve'))) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

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
