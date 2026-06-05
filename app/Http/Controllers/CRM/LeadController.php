<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Lead::with('assignedTo')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'source' => $lead->source,
                'assigned_to' => $lead->assignedTo ? $lead->assignedTo->name : 'Unassigned',
                'created_at' => $lead->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('CRM/Leads/Index', [
            'leads' => $leads,
            'title' => 'Leads Management'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:new,contacted,qualified,lost',
            'source' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        Lead::create($validated);

        return Redirect::back()->with('success', 'Lead created successfully.');
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:new,contacted,qualified,lost',
            'source' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $lead->update($validated);

        return Redirect::back()->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return Redirect::back()->with('success', 'Lead deleted successfully.');
    }
}
