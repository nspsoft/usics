<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ProductionEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
                'workOrder.salesOrder',
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

    public function edit(ProductionEntry $productionEntry): Response
    {
        $productionEntry->loadMissing([
            'workOrder.product',
            'operatorEmployee',
        ]);

        if (session()->has('company_id') && $productionEntry->workOrder && (int) $productionEntry->workOrder->company_id !== (int) session('company_id')) {
            abort(404);
        }

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ((int) $productionEntry->entry_user_id !== (int) $user->id && !$user->hasAnyRole(['Super Admin', 'Admin', 'Administrator'])) {
            abort(403);
        }

        return Inertia::render('Manufacturing/ProductionEntries/Edit', [
            'entry' => $productionEntry,
        ]);
    }

    public function update(Request $request, ProductionEntry $productionEntry): RedirectResponse
    {
        $productionEntry->loadMissing(['workOrder']);

        if (session()->has('company_id') && $productionEntry->workOrder && (int) $productionEntry->workOrder->company_id !== (int) session('company_id')) {
            abort(404);
        }

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ((int) $productionEntry->entry_user_id !== (int) $user->id && !$user->hasAnyRole(['Super Admin', 'Admin', 'Administrator'])) {
            abort(403);
        }

        $validated = $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'downtime_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $duplicate = $productionEntry->workOrder
            ->productionEntries()
            ->where('id', '!=', $productionEntry->id)
            ->whereDate('production_date', $productionEntry->production_date)
            ->where('shift', $productionEntry->shift)
            ->where('operator_employee_id', $productionEntry->operator_employee_id)
            ->when(
                array_key_exists('start_time', $validated) && $validated['start_time'] !== null && $validated['start_time'] !== '',
                fn ($q) => $q->where('start_time', $validated['start_time']),
                fn ($q) => $q->whereNull('start_time')
            )
            ->when(
                array_key_exists('end_time', $validated) && $validated['end_time'] !== null && $validated['end_time'] !== '',
                fn ($q) => $q->where('end_time', $validated['end_time']),
                fn ($q) => $q->whereNull('end_time')
            )
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'start_time' => 'Laporan produksi untuk operator/shift/jam yang sama sudah ada.',
            ]);
        }

        $productionEntry->update([
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'downtime_minutes' => $validated['downtime_minutes'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Laporan produksi berhasil di-update.');
    }

    public function printLabels(Request $request, ProductionEntry $productionEntry)
    {
        $productionEntry->loadMissing(['workOrder.product', 'workOrder.salesOrder.customer', 'operatorEmployee']);
        $labelData = json_decode($request->input('label_data'), true) ?? [];

        $labels = [];
        foreach ($labelData as $data) {
            $qtyPerLabel = $data['qty_per_label'] ?? $productionEntry->qty_produced;
            $labelCount = $data['label_count'] ?? 1;
            $lotNumber = $data['lot_number'] ?? '';
            $spk = $data['spk'] ?? '';
            $note = $data['note'] ?? '';
            $size = $data['size'] ?? '';
            $specification = $data['specification'] ?? '';

            for ($i = 0; $i < $labelCount; $i++) {
                $labels[] = [
                    'customer_name' => $productionEntry->workOrder->salesOrder->customer->name ?? 'STOCK',
                    'product_name' => $productionEntry->workOrder->product->name,
                    'sku' => $productionEntry->workOrder->product->sku,
                    'specification' => $specification,
                    'size' => $size,
                    'qty' => number_format($qtyPerLabel, 0, ',', '.') . ' Pcs',
                    'lot_number' => $lotNumber,
                    'spk' => $spk,
                    'note' => $note,
                ];
            }
        }

        return view('print.product-labels', [
            'labels' => $labels,
        ]);
    }
}
