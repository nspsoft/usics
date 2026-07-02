<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\OvertimeRequest;
use App\Models\Attendance;
use App\Models\PayrollSetting;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak terdaftar sebagai Karyawan (Employee). Silakan hubungi HR atau Administrator untuk mendaftarkan NIK dan menautkan akun Anda.');
        }

        $overtimes = OvertimeRequest::where('employee_id', $employee->id)
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Employee/Overtime/Index', [
            'overtimes' => $overtimes
        ]);
    }

    public function store(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->back()->with('error', 'Akun Anda tidak terdaftar sebagai Karyawan (Employee). Silakan hubungi HR atau Administrator untuk mendaftarkan NIK dan menautkan akun Anda.');
        }

        $validated = $request->validate([
            'type' => 'required|in:pre_planned,post_claim',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        // Check if there is already an overtime request for this date (excluding rejected)
        $exists = OvertimeRequest::where('employee_id', $employee->id)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['date' => 'Anda sudah memiliki pengajuan lembur yang pending atau disetujui pada tanggal ini.']);
        }

        // Validate post-claim against actual attendance
        if ($validated['type'] === 'post_claim') {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $validated['date'])
                ->first();

            if (!$attendance || !$attendance->clock_out) {
                return redirect()->back()->withErrors(['date' => 'Anda tidak memiliki riwayat absensi pulang (Clock Out) pada tanggal ini.']);
            }

            $standardEndStr = PayrollSetting::where('key', 'standard_end_time')->first()->value ?? '17:00';
            $standardEndTime = Carbon::parse($validated['date'] . ' ' . $standardEndStr);
            $clockOutTime = Carbon::parse($attendance->clock_out);

            if ($clockOutTime->lessThanOrEqualTo($standardEndTime)) {
                return redirect()->back()->withErrors(['date' => 'Anda tidak memenuhi syarat lembur (tidak ada kelebihan jam kerja setelah jam ' . $standardEndStr . ').']);
            }
        }

        // Calculate requested minutes
        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        $requestedMinutes = $start->diffInMinutes($end);

        if ($requestedMinutes <= 0) {
            return redirect()->back()->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'requested_minutes' => $requestedMinutes,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('employee.overtime.index')->with('success', 'Pengajuan lembur berhasil dikirim.');
    }
}
