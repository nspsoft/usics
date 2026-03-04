<?php

namespace App\Http\Controllers\Purchasing;

use App\Exports\SupplierContactExport;
use App\Exports\SupplierExport;
use App\Exports\Template\SupplierContactTemplateExport;
use App\Exports\Template\SupplierTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SupplierContactImport;
use App\Imports\SupplierImport;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): Response
    {
        $query = Supplier::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->when($request->status !== null, function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            });

        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $query->orderBy($sort, $direction);

        $suppliers = $query->paginate(9)->withQueryString();

        return Inertia::render('Purchasing/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): Response
    {
        return Inertia::render('Purchasing/Suppliers/Form', [
            'supplier' => null,
            'subcontWarehouses' => \App\Models\Warehouse::where('type', 'subcontract')->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:suppliers,code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'fax' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'npwp' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:20',
            'payment_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'subcontract_warehouse_id' => 'nullable|exists:warehouses,id',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:30',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.position' => 'nullable|string|max:100',
        ]);

        $supplier = Supplier::create($validated);

        if ($request->has('contacts')) {
            $supplier->contacts()->createMany($request->contacts);
        }

        return redirect()->route('purchasing.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): Response
    {
        $supplier->load(['contacts', 'purchaseOrders' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return Inertia::render('Purchasing/Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier): Response
    {
        $supplier->load('contacts');

        return Inertia::render('Purchasing/Suppliers/Form', [
            'supplier' => $supplier,
            'subcontWarehouses' => \App\Models\Warehouse::where('type', 'subcontract')->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:suppliers,code,'.$supplier->id,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'fax' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'npwp' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:20',
            'payment_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'subcontract_warehouse_id' => 'nullable|exists:warehouses,id',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:30',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.position' => 'nullable|string|max:100',
        ]);

        $supplier->update($validated);

        // Sync contacts: Delete old and create new/update
        // Simplest strategy: Delete all and recreate (or usage of arrays difference to update)
        // For simplicity let's stick to deleting existing and recreating, unless IDs are passed.
        // But usually frontend sends fresh array. Let's try to update logic if id exists.

        if ($request->has('contacts')) {
            // Get IDs from request
            $contactIds = collect($request->contacts)->pluck('id')->filter()->toArray();

            // Delete contacts not in request
            $supplier->contacts()->whereNotIn('id', $contactIds)->delete();

            foreach ($request->contacts as $contactData) {
                if (isset($contactData['id'])) {
                    $supplier->contacts()->where('id', $contactData['id'])->update($contactData);
                } else {
                    $supplier->contacts()->create($contactData);
                }
            }
        } else {
            // access contacts as empty array if nothing sent? or ignore?
            // If explicit empty array sent, keys mismatch, so safer logic is above.
        }

        return redirect()->route('purchasing.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Cannot delete supplier with existing purchase orders.');
        }

        $supplier->contacts()->delete(); // Delete contacts first
        $supplier->delete();

        return redirect()->route('purchasing.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    public function export()
    {
        return Excel::download(new SupplierExport, 'suppliers_'.now()->format('Y-m-d').'.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new SupplierImport($request->boolean('overwrite')), $request->file('file'));
            return back()->with('success', 'Suppliers imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\SupplierDataExport, 'suppliers_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new SupplierTemplateExport, 'suppliers_template.xlsx');
    }

    public function exportContacts()
    {
        return Excel::download(new SupplierContactExport, 'supplier_contacts_'.now()->format('Y-m-d').'.xlsx');
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new SupplierContactImport($request->boolean('overwrite')), $request->file('file'));
            return back()->with('success', 'Supplier contacts imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function templateContacts(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\SupplierContactDataExport, 'supplier_contacts_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new SupplierContactTemplateExport, 'supplier_contacts_template.xlsx');
    }
}
