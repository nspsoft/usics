<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HR\Reimbursement;

class ReimbursementController extends Controller
{
    public function index()
    {
        $reimbursements = Reimbursement::with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('HR/Reimbursements/Index', [
            'reimbursements' => $reimbursements
        ]);
    }
    
    public function show(Reimbursement $reimbursement)
    {
        $reimbursement->load(['employee', 'approvalRequest.workflow.steps', 'approvalRequest.histories.actedBy']);
            
        return Inertia::render('HR/Reimbursements/Show', [
            'reimbursement' => $reimbursement
        ]);
    }

    public function markAsPaid(Reimbursement $reimbursement)
    {
        if ($reimbursement->approval_status !== 'approved') {
            return back()->with('error', 'Only approved reimbursements can be marked as paid.');
        }

        $reimbursement->update(['status' => 'paid']);

        return back()->with('success', 'Reimbursement marked as paid.');
    }
}
