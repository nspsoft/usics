<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PayrollSetting;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->month ?: Carbon::now()->month;
        $year = $request->year ?: Carbon::now()->year;

        $payrolls = Payroll::with(['employee.department', 'employee.position'])
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->when($request->search, function($query, $search) {
                $query->whereHas('employee', function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HR/Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => [
                'month' => (int)$month,
                'year' => (int)$year,
                'search' => $request->search
            ]
        ]);
    }


    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        $month = $request->month;
        $year = $request->year;
        
        $employees = Employee::where('is_active', true)->get();
        $generatedCount = 0;

        // Fetch all payroll settings
        $settings = PayrollSetting::where('is_active', true)->get()->keyBy('key');

        DB::transaction(function () use ($employees, $month, $year, $settings, &$generatedCount) {
            foreach ($employees as $employee) {
                if (Payroll::where('employee_id', $employee->id)->where('period_month', $month)->where('period_year', $year)->exists()) {
                    continue;
                }

                // Presence Count
                $presence = Attendance::where('employee_id', $employee->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->get();

                $presentDays = $presence->whereIn('status', ['present', 'late'])->count();
                $lateDays = $presence->where('status', 'late')->count();
                
                // Sum minutes
                $totalLateMinutes = $presence->sum('late_minutes');
                $totalEarlyMinutes = $presence->sum('early_leave_minutes');
                // Sum minutes from approved overtime requests
                $totalOvertimeMinutes = \App\Models\HR\OvertimeRequest::where('employee_id', $employee->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->where('status', 'approved')
                    ->sum('approved_minutes');
                $totalOvertimeHours = round($totalOvertimeMinutes / 60, 2);

                // --- Allowances Calculation ---
                // Meal Allowance daily
                $mealRate = (double)($settings['meal_allowance_daily']->value ?? 25000);
                $mealAllowance = $presentDays * $mealRate;

                // Transport Allowance daily
                $transportRate = (double)($settings['transport_allowance_daily']->value ?? 15000);
                $transportAllowance = $presentDays * $transportRate;
                
                $totalAllowances = $mealAllowance + $transportAllowance;

                // --- Deductions Calculation ---
                $totalDeductions = 0;
                $deductionItems = [];

                // 1. Late Deduction (Fixed per incident)
                $lateRate = (double)($settings['late_deduction_fixed']->value ?? 0);
                if ($lateRate > 0 && $lateDays > 0) {
                    $lateAmount = $lateDays * $lateRate;
                    $totalDeductions += $lateAmount;
                    $deductionItems[] = [
                        'name' => "Lateness Deduction ({$lateDays} incidents)",
                        'amount' => $lateAmount,
                        'type' => 'deduction'
                    ];
                }

                // 2. Early Leave Deduction (Fixed per incident)
                $earlyRate = (double)($settings['early_leave_deduction_fixed']->value ?? 0);
                $earlyIncidents = $presence->where('early_leave_minutes', '>', 0)->count();
                if ($earlyRate > 0 && $earlyIncidents > 0) {
                    $earlyAmount = $earlyIncidents * $earlyRate;
                    $totalDeductions += $earlyAmount;
                    $deductionItems[] = [
                        'name' => "Early Leave Deduction ({$earlyIncidents} incidents)",
                        'amount' => $earlyAmount,
                        'type' => 'deduction'
                    ];
                }

                // 3. Absent Deduction (Formula: (Salary / Divisor) * Absent Days)
                $daysInMonth = Carbon::create($year, $month)->daysInMonth;
                $absentDays = max(0, $daysInMonth - $presentDays - 8); 
                
                $absentDivisor = (double)($settings['absent_deduction_formula']->value ?? 25);
                if ($absentDivisor > 0 && $absentDays > 0) {
                    $absentAmount = ($employee->basic_salary / $absentDivisor) * $absentDays;
                    $totalDeductions += $absentAmount;
                    $deductionItems[] = [
                        'name' => "Absenteeism Deduction ({$absentDays} days)",
                        'amount' => $absentAmount,
                        'type' => 'deduction'
                    ];
                }

                // --- Overtime Calculation ---
                $overtimeAmount = 0;
                if ($totalOvertimeHours > 0) {
                    $rateType = $settings['overtime_rate_type']->value ?? '1';
                    if ($rateType == '1') { // Standard/Formula (Salary/173)
                        $divisor = (double)($settings['overtime_divisor']->value ?? 173);
                        $hourlyRate = $employee->basic_salary / $divisor;
                        $overtimeAmount = $totalOvertimeHours * $hourlyRate;
                    } else { // Fixed Amount
                        $fixedRate = (double)($settings['overtime_fixed_amount']->value ?? 20000);
                        $overtimeAmount = $totalOvertimeHours * $fixedRate;
                    }
                }

                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'basic_salary' => $employee->basic_salary,
                    'total_allowances' => $totalAllowances + $overtimeAmount,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $employee->basic_salary + $totalAllowances + $overtimeAmount - $totalDeductions,
                    'status' => 'draft',
                ]);

                // Record Detail Items
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "Meal Allowance ({$presentDays} days)",
                    'amount' => $mealAllowance,
                    'type' => 'allowance'
                ]);

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => "Transport Allowance ({$presentDays} days)",
                    'amount' => $transportAllowance,
                    'type' => 'allowance'
                ]);

                if ($overtimeAmount > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => "Overtime ({$totalOvertimeHours} hours)",
                        'amount' => $overtimeAmount,
                        'type' => 'allowance'
                    ]);
                }

                foreach ($deductionItems as $item) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'type' => 'deduction'
                    ]);
                }

                $generatedCount++;
            }
        });

        return redirect()->back()->with('success', "Payroll generated for {$generatedCount} employees.");
    }

    public function show(Payroll $payroll): Response
    {
        return Inertia::render('HR/Payroll/Show', [
            'payroll' => $payroll->load(['employee.department', 'employee.position', 'items'])
        ]);
    }

    public function updateStatus(Request $request, Payroll $payroll)
    {
        $request->validate(['status' => 'required|in:confirmed,paid,cancelled']);
        
        $data = ['status' => $request->status];
        if ($request->status === 'paid') {
            $data['payment_date'] = Carbon::now();
        }

        $payroll->update($data);

        return redirect()->back()->with('success', "Payroll status updated to {$request->status}.");
    }

    public function print(Payroll $payroll)
    {
        return view('print.payslip', [
            'payroll' => $payroll->load(['employee.department', 'employee.position', 'items'])
        ]);
    }

    public function publicValidate($id)
    {
        $payroll = Payroll::with(['employee.department', 'employee.position'])
            ->findOrFail($id);

        return view('print.public-payslip-validation', [
            'payroll' => $payroll
        ]);
    }
}
