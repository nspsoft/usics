<?php

namespace App\Traits;

use App\Models\ApprovalRequest;
use App\Models\Workflow;

trait HasApproval
{
    /**
     * Boot the trait
     */
    public static function bootHasApproval()
    {
        // When document is created, check if approval is needed
        static::created(function ($model) {
            $model->checkAndCreateApprovalRequest();
        });
    }

    /**
     * Check if document needs approval and create request
     */
    public function checkAndCreateApprovalRequest(): ?ApprovalRequest
    {
        $documentType = class_basename($this);
        $workflow = Workflow::findForDocument($documentType, $this);

        if (!$workflow) {
            return null; // No approval needed
        }

        if ($workflow->is_auto_approve) {
            // Create approval request with status approved
            $request = ApprovalRequest::create([
                'workflow_id' => $workflow->id,
                'document_type' => $documentType,
                'document_id' => $this->id,
                'current_step' => 0,
                'status' => ApprovalRequest::STATUS_APPROVED,
                'requested_by' => auth()->id() ?? 1,
                'completed_at' => now(),
            ]);

            \App\Models\ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'step_order' => 0,
                'action' => 'approved',
                'acted_by' => auth()->id() ?? 1,
                'notes' => 'Auto Approved by System',
            ]);

            $this->approval_status = 'approved';
            
            // Also update the document status if it has custom update logic
            if (method_exists($this, 'updateApprovalStatus')) {
                $this->updateApprovalStatus('approved');
            } else {
                $this->saveQuietly();
            }

            return $request;
        }

        // Create approval request
        $request = ApprovalRequest::create([
            'workflow_id' => $workflow->id,
            'document_type' => $documentType,
            'document_id' => $this->id,
            'current_step' => 1,
            'status' => ApprovalRequest::STATUS_PENDING,
            'requested_by' => auth()->id() ?? 1,
        ]);

        // Update document status
        $this->approval_status = 'pending';
        $this->saveQuietly();

        return $request;
    }

    /**
     * Get approval request for this document
     */
    public function approvalRequest()
    {
        return $this->hasOne(ApprovalRequest::class, 'document_id')
            ->where('document_type', class_basename($this));
    }

    /**
     * Check if document is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if document is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Update approval status (called by ApprovalRequest)
     */
    public function updateApprovalStatus(string $status): void
    {
        $this->approval_status = $status;
        $this->saveQuietly();
    }

    /**
     * Get approval status badge color
     */
    public function getApprovalStatusColorAttribute(): string
    {
        return match ($this->approval_status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get approval status label
     */
    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'No Approval Required',
        };
    }
}
