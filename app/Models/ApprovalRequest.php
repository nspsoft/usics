<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'document_type',
        'document_id',
        'current_step',
        'status',
        'requested_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get parent workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get requester
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get approval histories
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class)->orderBy('created_at');
    }

    /**
     * Get the document
     */
    public function getDocumentAttribute()
    {
        $modelClass = 'App\\Models\\' . $this->document_type;
        if (class_exists($modelClass)) {
            return $modelClass::find($this->document_id);
        }
        return null;
    }

    /**
     * Get current workflow step
     */
    public function currentWorkflowStep(): ?WorkflowStep
    {
        return $this->workflow->steps()->where('step_order', $this->attributes['current_step'])->first();
    }

    /**
     * Check if user can approve current step
     */
    public function canBeApprovedBy(User $user): bool
    {
        $step = $this->currentWorkflowStep();
        return $step && $step->canBeApprovedBy($user);
    }

    /**
     * Approve current step
     */
    public function approve(User $user, ?string $notes = null): bool
    {
        $currentStep = $this->currentWorkflowStep();
        
        if (!$currentStep || !$currentStep->canBeApprovedBy($user)) {
            return false;
        }

        // Record history
        ApprovalHistory::create([
            'approval_request_id' => $this->id,
            'step_order' => $this->current_step,
            'action' => 'approved',
            'acted_by' => $user->id,
            'notes' => $notes,
        ]);

        // Check if this was the last step
        $nextStep = $this->workflow->steps()->where('step_order', '>', $this->current_step)->first();
        
        if ($nextStep) {
            $this->current_step = $nextStep->step_order;
            $this->save();
        } else {
            // All steps completed
            $this->status = self::STATUS_APPROVED;
            $this->completed_at = now();
            $this->save();

            // Update document status
            $this->updateDocumentStatus('approved');
        }

        return true;
    }

    /**
     * Reject approval request
     */
    public function reject(User $user, ?string $notes = null): bool
    {
        $currentStep = $this->currentWorkflowStep();
        
        if (!$currentStep || !$currentStep->canBeApprovedBy($user)) {
            return false;
        }

        // Record history
        ApprovalHistory::create([
            'approval_request_id' => $this->id,
            'step_order' => $this->current_step,
            'action' => 'rejected',
            'acted_by' => $user->id,
            'notes' => $notes,
        ]);

        $this->status = self::STATUS_REJECTED;
        $this->completed_at = now();
        $this->save();

        // Update document status
        $this->updateDocumentStatus('rejected');

        return true;
    }

    /**
     * Update the document's approval status
     */
    protected function updateDocumentStatus(string $status): void
    {
        $document = $this->document;
        if ($document && method_exists($document, 'updateApprovalStatus')) {
            $document->updateApprovalStatus($status);
        } elseif ($document && isset($document->approval_status)) {
            $document->approval_status = $status;
            $document->save();
        }
    }

    /**
     * Create approval request for a document
     */
    public static function createForDocument($document, User $requester): ?self
    {
        $documentType = class_basename($document);
        $workflow = Workflow::findForDocument($documentType, $document);

        if (!$workflow) {
            return null; // No workflow applies
        }

        return static::create([
            'workflow_id' => $workflow->id,
            'document_type' => $documentType,
            'document_id' => $document->id,
            'current_step' => 1,
            'status' => self::STATUS_PENDING,
            'requested_by' => $requester->id,
        ]);
    }

    /**
     * Get pending approvals for a user
     */
    public static function getPendingForUser(User $user)
    {
        return static::where('status', self::STATUS_PENDING)
            ->get()
            ->filter(fn ($request) => $request->canBeApprovedBy($user));
    }
}
