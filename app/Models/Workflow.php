<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document_type',
        'description',
        'is_active',
        'is_auto_approve',
        'condition_field',
        'condition_operator',
        'condition_value',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_auto_approve' => 'boolean',
            'condition_value' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    /**
     * Get workflow steps
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('step_order');
    }

    /**
     * Get approval requests using this workflow
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    /**
     * Get document types available for workflows
     */
    public static function getDocumentTypes(): array
    {
        return [
            // Purchasing
            'PurchaseRequest' => 'Purchase Request',
            'PurchaseOrder' => 'Purchase Order',
            'SubcontractOrder' => 'Subcontract Order',
            'PurchaseInvoice' => 'Purchase Invoice',
            'PurchaseReturn' => 'Purchase Return',
            'PurchasePayment' => 'Purchase Payment',

            // Sales
            'Quotation' => 'Sales Quotation',
            'SalesOrder' => 'Sales Order',
            'DeliveryOrder' => 'Delivery Order',
            'SalesInvoice' => 'Sales Invoice',
            'SalesReturn' => 'Sales Return',

            // Manufacturing & Inventory
            'WorkOrder' => 'Work Order',
            'StockOpname' => 'Stock Opname',
            'StockTransfer' => 'Stock Transfer',
            'StockAdjustment' => 'Stock Adjustment',

            // Finance & HR
            'Payroll' => 'Payroll',
            'CoaDocument' => 'Jurnal Akuntansi',
        ];
    }

    /**
     * Get condition operators
     */
    public static function getConditionOperators(): array
    {
        return [
            '>' => 'Greater than',
            '>=' => 'Greater than or equal',
            '<' => 'Less than',
            '<=' => 'Less than or equal',
            '=' => 'Equal to',
        ];
    }

    /**
     * Find applicable workflow for a document
     */
    public static function findForDocument(string $documentType, $document): ?self
    {
        $workflows = static::where('document_type', $documentType)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($workflows as $workflow) {
            if ($workflow->matchesCondition($document)) {
                return $workflow;
            }
        }

        return null;
    }

    /**
     * Check if document matches workflow condition
     */
    public function matchesCondition($document): bool
    {
        // No condition = always match
        if (!$this->condition_field || !$this->condition_operator) {
            return true;
        }

        $value = $document->{$this->condition_field} ?? 0;
        $threshold = $this->condition_value;

        return match ($this->condition_operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '=' => $value == $threshold,
            default => false,
        };
    }
}
