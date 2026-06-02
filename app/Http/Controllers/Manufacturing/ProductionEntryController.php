<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ProductionEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'shift' => 'nullable|string|max:10',
            'operator_employee_id' => 'nullable|integer',
        ]);

        $query = ProductionEntry::query()
            ->with([
                'workOrder.product',
                'operatorEmployee',
                'entryUser',
            ])
            ->when(($validated['search'] ?? null), function ($q, $search) {
                $q->whereHas('workOrder', function ($wo) use ($search) {
                    $wo->where('wo_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when(($validated['date_from'] ?? null), fn ($q, $dateFrom) => $q->whereDate('production_date', '>=', $dateFrom))
            ->when(($validated['date_to'] ?? null), fn ($q, $dateTo) => $q->whereDate('production_date', '<=', $dateTo))
            ->when(($validated['shift'] ?? null), fn ($q, $shift) => $q->where('shift', $shift))
            ->when(($validated['operator_employee_id'] ?? null), fn ($q, $id) => $q->where('operator_employee_id', $id))
            ->orderByDesc('production_date')
            ->orderByDesc('id');

        $entries = $query->paginate(25)->withQueryString();

        $operators = Employee::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'nik', 'full_name']);

        return Inertia::render('Manufacturing/ProductionEntries/Index', [
            'entries' => $entries,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'shift', 'operator_employee_id']),
            'operators' => $operators,
        ]);
    }
}

