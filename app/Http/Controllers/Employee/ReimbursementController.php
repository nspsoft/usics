<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Reimbursement;
use Carbon\Carbon;

class ReimbursementController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'You are not registered as an employee.');
        }

        $reimbursements = Reimbursement::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Employee/Reimbursements/Index', [
            'reimbursements' => $reimbursements
        ]);
    }

    public function create()
    {
        return Inertia::render('Employee/Reimbursements/Create');
    }

    public function store(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $filename = 'receipt_' . $employee->id . '_' . time() . '.' . $file->extension();
            $receiptPath = $file->storeAs('reimbursement_receipts', $filename, 'public');
        }

        $reimbursementNumber = 'REIMB-' . date('Ymd') . '-' . str_pad(Reimbursement::count() + 1, 4, '0', STR_PAD_LEFT);

        $reimbursement = Reimbursement::create([
            'reimbursement_number' => $reimbursementNumber,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'receipt_path' => $receiptPath,
            'status' => 'submitted',
            'approval_status' => 'pending',
        ]);

        return redirect()->route('employee.reimbursements.index')->with('success', 'Reimbursement submitted successfully.');
    }
    
    public function show($id)
    {
        $employee = auth()->user()->employee;
        $reimbursement = Reimbursement::with(['employee', 'approvalRequest.workflow.steps', 'approvalRequest.histories.actedBy'])
            ->where('employee_id', $employee->id)
            ->findOrFail($id);
            
        return Inertia::render('Employee/Reimbursements/Show', [
            'reimbursement' => $reimbursement
        ]);
    }
}
