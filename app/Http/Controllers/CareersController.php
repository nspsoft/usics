<?php

namespace App\Http\Controllers;

use App\Models\HR\JobPosting;
use App\Models\HR\Applicant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;

class CareersController extends Controller
{
    public function index()
    {
        $jobs = JobPosting::with('department')
            ->where('status', 'Open')
            ->where(function ($query) {
                $query->whereNull('closing_date')
                      ->orWhere('closing_date', '>=', now()->toDateString());
            })
            ->latest()
            ->get();

        return Inertia::render('Careers/Index', [
            'jobs' => $jobs
        ]);
    }

    public function show(JobPosting $job)
    {
        if ($job->status !== 'Open' || ($job->closing_date && $job->closing_date < now()->toDateString())) {
            abort(404, 'Job posting is no longer available.');
        }

        $job->load('department');

        return Inertia::render('Careers/Show', [
            'job' => $job
        ]);
    }

    public function apply(Request $request, JobPosting $job)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'resume' => 'required|file|mimes:pdf|max:5120', // Max 5MB PDF
        ]);

        $resumePath = $request->file('resume')->store('resumes', 'public');

        // Resume Parsing
        $parser = new Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $resumePath));
        $text = strtolower($pdf->getText());

        // Simple Keyword Matching (Extract from job requirements)
        // Usually, in a real scenario, we extract keywords from job description via AI
        // Here we just split by space and commas from the 'requirements' column to find matches
        
        $reqText = strtolower($job->requirements);
        // Remove symbols
        $reqText = preg_replace('/[^a-z0-9 ]+/', ' ', $reqText);
        $reqWords = array_unique(array_filter(explode(' ', $reqText), function($w) {
            return strlen($w) > 3; // Only consider words > 3 chars
        }));

        $matchedWords = [];
        foreach ($reqWords as $word) {
            if (strpos($text, $word) !== false) {
                $matchedWords[] = $word;
            }
        }

        $matchScore = 0;
        if (count($reqWords) > 0) {
            $matchScore = round((count($matchedWords) / count($reqWords)) * 100);
        }

        Applicant::create([
            'job_posting_id' => $job->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'resume_path' => $resumePath,
            'parsed_skills' => implode(', ', $matchedWords),
            'match_score' => $matchScore,
            'status' => 'Applied'
        ]);

        return redirect()->route('careers.index')->with('success', 'Your application has been submitted successfully.');
    }
}
