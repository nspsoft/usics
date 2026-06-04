<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class WorkflowController extends Controller
{
    /**
     * Display workflow configuration page
     */
    public function index()
    {
        return Inertia::render('Settings/WorkflowApproval', [
            'workflows' => Workflow::with('steps')->orderBy('priority', 'desc')->get(),
            'documentTypes' => Workflow::getDocumentTypes(),
            'conditionOperators' => Workflow::getConditionOperators(),
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'roles' => Role::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new workflow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'document_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_auto_approve' => 'boolean',
            'condition_field' => 'nullable|string|max:50',
            'condition_operator' => 'nullable|string|max:20',
            'condition_value' => 'nullable|numeric',
            'priority' => 'integer',
            'steps' => 'required_unless:is_auto_approve,true|array|min:1',
            'steps.*.approver_type' => 'required_unless:is_auto_approve,true|in:user,role',
            'steps.*.approver_id' => 'required_unless:is_auto_approve,true|integer',
            'steps.*.can_skip' => 'boolean',
            'steps.*.timeout_days' => 'nullable|integer|min:1',
        ]);

        $workflow = Workflow::create([
            'name' => $validated['name'],
            'document_type' => $validated['document_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_auto_approve' => $validated['is_auto_approve'] ?? false,
            'condition_field' => $validated['condition_field'],
            'condition_operator' => $validated['condition_operator'],
            'condition_value' => $validated['condition_value'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        // Create steps if not auto approve
        if (empty($validated['is_auto_approve'])) {
            foreach ($validated['steps'] ?? [] as $index => $step) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_order' => $index + 1,
                    'approver_type' => $step['approver_type'],
                    'approver_id' => $step['approver_id'],
                    'can_skip' => $step['can_skip'] ?? false,
                    'timeout_days' => $step['timeout_days'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Workflow created successfully.');
    }

    /**
     * Update a workflow
     */
    public function update(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'document_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_auto_approve' => 'boolean',
            'condition_field' => 'nullable|string|max:50',
            'condition_operator' => 'nullable|string|max:20',
            'condition_value' => 'nullable|numeric',
            'priority' => 'integer',
            'steps' => 'required_unless:is_auto_approve,true|array|min:1',
            'steps.*.approver_type' => 'required_unless:is_auto_approve,true|in:user,role',
            'steps.*.approver_id' => 'required_unless:is_auto_approve,true|integer',
            'steps.*.can_skip' => 'boolean',
            'steps.*.timeout_days' => 'nullable|integer|min:1',
        ]);

        $workflow->update([
            'name' => $validated['name'],
            'document_type' => $validated['document_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_auto_approve' => $validated['is_auto_approve'] ?? false,
            'condition_field' => $validated['condition_field'],
            'condition_operator' => $validated['condition_operator'],
            'condition_value' => $validated['condition_value'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        // Delete existing steps and recreate if not auto approve
        $workflow->steps()->delete();
        
        if (empty($validated['is_auto_approve'])) {
            foreach ($validated['steps'] ?? [] as $index => $step) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_order' => $index + 1,
                    'approver_type' => $step['approver_type'],
                    'approver_id' => $step['approver_id'],
                    'can_skip' => $step['can_skip'] ?? false,
                    'timeout_days' => $step['timeout_days'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Workflow updated successfully.');
    }

    /**
     * Delete a workflow
     */
    public function destroy(Workflow $workflow)
    {
        $workflow->delete();
        return back()->with('success', 'Workflow deleted successfully.');
    }

    /**
     * Toggle workflow active status
     */
    public function toggle(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);
        return back()->with('success', 'Workflow status updated.');
    }
}
