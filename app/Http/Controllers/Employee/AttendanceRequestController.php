<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\HR\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceRequestController extends Controller
{
    /**
     * Store a newly created attendance request in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->employee) {
            return redirect()->back()->with('error', 'Akses ditolak: Data karyawan tidak ditemukan.');
        }

        $validated = $request->validate([
            'type' => 'required|string|in:late_arrival,early_dismissal,forgot_clock_in',
            'request_date' => 'required|date',
            'request_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        $employeeId = $user->employee->id;

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attendance_requests', 'public');
        }

        AttendanceRequest::create([
            'employee_id' => $employeeId,
            'type' => $validated['type'],
            'request_date' => $validated['request_date'],
            'request_time' => $validated['request_time'],
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan izin absensi berhasil dikirim dan menunggu persetujuan HR.');
    }
}
