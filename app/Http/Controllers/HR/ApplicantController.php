<?php

namespace App\Http\Controllers\HR;

use App\Models\HR\Applicant;
use App\Models\HR\JobPosting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ApplicantController extends Controller
{
    public function index()
    {
        $applicants = Applicant::with('jobPosting.department')->latest()->get();
        return Inertia::render('HR/Recruitment/Applicants/Index', [
            'applicants' => $applicants
        ]);
    }

    public function updateStatus(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'status' => 'required|in:Applied,Interview,Hired,Rejected'
        ]);

        $applicant->update($validated);

        return redirect()->back()->with('success', 'Applicant status updated.');
    }
}
