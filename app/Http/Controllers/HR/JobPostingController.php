<?php

namespace App\Http\Controllers\HR;

use App\Models\HR\JobPosting;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class JobPostingController extends Controller
{
    public function index()
    {
        $jobs = JobPosting::with('department')->latest()->get();
        return Inertia::render('HR/Recruitment/Jobs/Index', [
            'jobs' => $jobs
        ]);
    }

    public function create()
    {
        return Inertia::render('HR/Recruitment/Jobs/Form', [
            'departments' => Department::where('is_active', true)->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:hr_departments,id',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'status' => 'required|in:Open,Closed',
            'closing_date' => 'nullable|date',
        ]);

        JobPosting::create($validated);

        return redirect()->route('hr.jobs.index')->with('success', 'Job Posting created successfully.');
    }

    public function edit(JobPosting $job)
    {
        return Inertia::render('HR/Recruitment/Jobs/Form', [
            'job' => $job,
            'departments' => Department::where('is_active', true)->get()
        ]);
    }

    public function update(Request $request, JobPosting $job)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:hr_departments,id',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'status' => 'required|in:Open,Closed',
            'closing_date' => 'nullable|date',
        ]);

        $job->update($validated);

        return redirect()->route('hr.jobs.index')->with('success', 'Job Posting updated successfully.');
    }

    public function destroy(JobPosting $job)
    {
        $job->delete();
        return redirect()->route('hr.jobs.index')->with('success', 'Job Posting deleted successfully.');
    }
}
