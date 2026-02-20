<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class POExtractorController extends Controller
{
    /**
     * Display the AI PO Extractor page.
     */
    public function index()
    {
        return Inertia::render('Sales/POExtractor', [
            'customers' => \App\Models\Customer::select('id', 'name', 'code')->orderBy('name')->get(),
            'units' => \App\Models\Unit::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'categories' => \App\Models\Category::select('id', 'name')->where('type', 'product')->orderBy('name')->get(),
        ]);
    }
    /**
     * Export extracted data to Excel.
     */
    public function export(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'po_number' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'po_date' => 'nullable|date',
            'items' => 'required|array',
        ]);

        $fileName = 'PO_Extraction_' . str_replace(['/', '\\'], '_', $data['po_number'] ?? 'Draft') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PoExtractionExport($data), $fileName);
    }

    /**
     * Quick register a new customer from PO Extractor.
     */
    public function storeCustomer(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        // Auto-generate code if not provided
        if (empty($data['code'])) {
            $lastCustomer = \App\Models\Customer::orderByDesc('id')->first();
            $nextNum = ($lastCustomer ? $lastCustomer->id : 0) + 1;
            $data['code'] = 'CUST-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }

        $customer = \App\Models\Customer::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ],
            'message' => 'Customer "' . $customer->name . '" berhasil didaftarkan.',
        ]);
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:units,code',
        ]);

        $unit = \App\Models\Unit::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'company_id' => 1,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
            ],
            'message' => 'Satuan "' . $unit->name . '" berhasil didaftarkan.',
        ]);
    }
}
