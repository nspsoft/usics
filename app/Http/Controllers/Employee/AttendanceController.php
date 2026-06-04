<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\AppSetting;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;
        if (!$employee) abort(403);

        $attendances = Attendance::where('employee_id', $employee->id)
            ->latest('date')
            ->paginate(15);

        return Inertia::render('Employee/Attendance/Index', [
            'attendances' => $attendances
        ]);
    }

    public function clockInOut()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'You are not registered as an employee.');
        }

        $appSettings = AppSetting::first();
        $today = Carbon::today();

        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return Inertia::render('Employee/Attendance/ClockInOut', [
            'employee' => $employee,
            'appSettings' => $appSettings,
            'todayAttendance' => $todayAttendance
        ]);
    }

    public function processClock(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'type' => 'required|in:in,out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|file|mimes:jpg,jpeg,png|max:4096',
            'face_descriptor' => 'required|string',
        ]);

        $appSettings = AppSetting::first();
        $distance = null;
        $isGeofenced = false;

        // Calculate Geofencing
        if ($appSettings && $appSettings->office_latitude && $appSettings->office_longitude) {
            $distance = $this->haversineGreatCircleDistance(
                $request->latitude, $request->longitude,
                $appSettings->office_latitude, $appSettings->office_longitude
            );
            $maxRadius = $appSettings->max_radius_meters ?? 50;
            $isGeofenced = $distance <= $maxRadius;
        } else {
            $isGeofenced = true; // No office location set
        }

        // Verify Face
        $isFaceVerified = false;
        if ($employee->face_descriptor) {
            $registeredFace = json_decode($employee->face_descriptor, true);
            $currentFace = json_decode($request->face_descriptor, true);
            
            // Simple Euclidean distance for face matching
            $faceDistance = 0;
            for ($i = 0; $i < count($registeredFace); $i++) {
                $faceDistance += pow($registeredFace[$i] - $currentFace[$i], 2);
            }
            $faceDistance = sqrt($faceDistance);
            
            // Typical threshold for face-api.js euclidean distance is 0.6
            $isFaceVerified = $faceDistance < 0.6;
        } else {
            // Force face verification to fail if not registered
            $isFaceVerified = false;
        }

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'clock_' . $request->type . '_' . $employee->id . '_' . time() . '.' . $file->extension();
            $photoPath = $file->storeAs('attendance_photos', $filename, 'public');
        }

        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            ['status' => 'present']
        );

        if ($request->type === 'in') {
            $attendance->time_in = $now->format('H:i:s');
            $attendance->photo_in_path = $photoPath;
            $attendance->distance_in_meters = $distance;
            $attendance->is_geofenced = $isGeofenced;
            $attendance->is_face_verified = $isFaceVerified;
        } else {
            $attendance->time_out = $now->format('H:i:s');
            $attendance->photo_out_path = $photoPath;
            $attendance->distance_out_meters = $distance;
            // For simplicity, we could store 'out' verification separately, but we'll just update it
            // Or you can create a separate boolean, e.g., is_face_verified_out. For now, we update it.
            $attendance->is_face_verified = $isFaceVerified;
        }

        $attendance->save();

        $message = "Clocked {$request->type} successfully.";
        if (!$isGeofenced) {
            $message .= " Note: You were out of range.";
        }
        if (!$isFaceVerified) {
            $message .= " Note: Face verification failed. Please contact HR.";
        }

        return redirect()->back()->with('success', $message);
    }

    private function haversineGreatCircleDistance($latFrom, $lonFrom, $latTo, $lonTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lonFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lonTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
