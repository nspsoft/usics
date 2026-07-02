<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    public function create(Request $request)
    {
        // Select Sales Order to generate COA for
        $salesOrders = \App\Models\SalesOrder::with('customer')
            ->whereIn('status', [\App\Models\SalesOrder::STATUS_DELIVERED, \App\Models\SalesOrder::STATUS_SHIPPED])
            ->whereDoesntHave('coaDocuments') // One COA per SO for now? Or per line?
            ->latest()
            ->paginate(10);

        return inertia('QualityControl/COA/Create', [
            'salesOrders' => $salesOrders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'customer_id' => 'required|exists:customers,id',
            'issued_date' => 'required|date',
            'coa_number' => 'required|string|unique:coa_documents,coa_number',
            // Add dynamic batch/results if needed
        ]);
        
        $validated['batch_number'] = 'BCH-' . strtoupper(\Illuminate\Support\Str::random(6));
        
        $coa = \App\Models\CoaDocument::create($validated);

        return redirect()->route('qc.coa.show', $coa->id)->with('success', 'COA generated successfully.');
    }

    public function show($id)
    {
        $coa = \App\Models\CoaDocument::with(['salesOrder.items.product', 'customer'])->findOrFail($id);
        
        return inertia('QualityControl/COA/Show', [
            'coa' => $coa,
        ]);
    }

    public function print($id)
    {
        $coa = \App\Models\CoaDocument::with(['salesOrder.items.product', 'customer'])->findOrFail($id);
        
        // Use a simple blade view for PDF printing (window.print())
        return view('print.coa', compact('coa'));
    }
}
