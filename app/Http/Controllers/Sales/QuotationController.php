<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuotationExport;
use App\Imports\QuotationImport;
use App\Exports\Template\QuotationTemplateExport;

class QuotationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Quotation::with(['customer', 'createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'customer_name') {
            $query->join('customers', 'quotations.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('quotations.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $quotations = $query->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Quotations/Index', [
            'quotations' => $quotations,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'sent', 'label' => 'Sent'],
                ['value' => 'accepted', 'label' => 'Accepted'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'expired', 'label' => 'Expired'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Quotations/Create', [
            'customers' => Customer::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->select('id','sku','name','unit_id','cost_price','selling_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'quotationNumber' => Quotation::generateNumber(),
        ]);
    }

    public function generateNextNumber(Request $request)
    {
        $customerId = $request->input('customer_id');
        $date = $request->input('quotation_date');
        return response()->json([
            'number' => Quotation::generateNumber($customerId, $date)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $quotation = Quotation::create([
                'number' => Quotation::generateNumber($validated['customer_id'], $validated['quotation_date']),
                'customer_id' => $validated['customer_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $quotation->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['qty'] * $item['unit_price'],
                ]);
            }

            $quotation->calculateTotal();
        });

        return redirect()->route('sales.quotations.index')
            ->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation): Response
    {
        $quotation->load(['customer', 'items.product', 'createdBy']);

        return Inertia::render('Sales/Quotations/Show', [
            'quotation' => $quotation,
        ]);
    }

    public function edit(Quotation $quotation): Response
    {
        $quotation->load(['items']);

        return Inertia::render('Sales/Quotations/Edit', [
            'quotation' => $quotation,
            'customers' => Customer::active()->orderBy('name')->get(),
            'products' => Product::active()->where('is_sold', true)->select('id','sku','name','unit_id','cost_price','selling_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if (in_array($quotation->status, ['accepted', 'converted'])) {
            return back()->with('error', 'Accepted or Converted quotations cannot be edited.');
        }

        $updateData = [
            'customer_id' => $validated['customer_id'],
            'quotation_date' => $validated['quotation_date'],
            'valid_until' => $validated['valid_until'],
            'notes' => $validated['notes'] ?? null,
        ];

        // Handle revision if status is SENT
        if ($quotation->status === 'sent') {
             $baseNumber = $quotation->number;
             // Remove existing revision suffix if any to get base
             if (strpos($baseNumber, '/REV-') !== false) {
                 $baseNumber = substr($baseNumber, 0, strpos($baseNumber, '/REV-'));
             }
             
             // Increment revision
             $newRevision = ($quotation->revision ?? 0) + 1;
             
             $updateData['status'] = 'draft';
             $updateData['revision'] = $newRevision;
             $updateData['number'] = $baseNumber . '/REV-' . $newRevision;
        }

        DB::transaction(function () use ($updateData, $validated, $quotation) {
            $quotation->update($updateData);

            // Sync items (Delete all and recreate)
            $quotation->items()->delete();

            foreach ($validated['items'] as $item) {
                $quotation->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['qty'] * $item['unit_price'],
                ]);
            }

            $quotation->calculateTotal();
        });

        return redirect()->route('sales.quotations.show', $quotation->id)
            ->with('success', 'Quotation updated successfully.');
    }

    public function send(Quotation $quotation)
    {
        $quotation->update(['status' => 'sent']);
        return back()->with('success', 'Quotation sent to customer.');
    }

    public function accept(Quotation $quotation)
    {
        $quotation->update(['status' => 'accepted']);
        return back()->with('success', 'Quotation accepted.');
    }

    public function reject(Quotation $quotation)
    {
        $quotation->update(['status' => 'rejected']);
        return back()->with('success', 'Quotation rejected.');
    }

    public function convertToSO(Quotation $quotation)
    {
        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Only accepted quotations can be converted to Sales Order.');
        }

        return DB::transaction(function () use ($quotation) {
            $so = \App\Models\SalesOrder::create([
                'company_id' => $quotation->customer->company_id ?? 1,
                'so_number' => \App\Models\SalesOrder::generateSoNumber(),
                'customer_id' => $quotation->customer_id,
                'warehouse_id' => \App\Models\Warehouse::first()->id ?? 1,
                'order_date' => now(),
                'status' => 'draft',
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax,
                'tax_percent' => 11,
                'total' => $quotation->total,
                'notes' => "Converted from Quotation #{$quotation->number}",
                'created_by' => auth()->id(),
            ]);

            foreach ($quotation->items as $item) {
                $so->items()->create([
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'unit_id' => $item->product->unit_id,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->total_price,
                ]);
            }

            $quotation->update(['status' => 'converted']);

            return redirect()->route('sales.orders.edit', $so->id)
                ->with('success', 'Quotation converted to Sales Order successfully.');
        });
    }

    public function print(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product', 'createdBy']);
        return view('print.quotation', ['quotation' => $quotation]);
    }

    public function publicValidate($uuid)
    {
        if (Str::isUuid($uuid)) {
            $quotation = Quotation::with(['customer', 'items.product'])
                ->where('public_uuid', $uuid)
                ->firstOrFail();
        } else {
            $quotation = Quotation::with(['customer', 'items.product'])
                ->findOrFail($uuid);

            if (!empty($quotation->public_uuid)) {
                return redirect()->route('sales.quotations.public-validate', $quotation->public_uuid);
            }
        }

        return view('print.public-quotation-validation', [
            'quotation' => $quotation
        ]);
    }
    public function destroy(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return back()->with('error', 'Only draft quotations can be deleted.');
        }

        $quotation->delete();

        return redirect()->route('sales.quotations.index')
            ->with('success', 'Quotation deleted successfully.');
    }

    public function export()
    {
        return Excel::download(new QuotationExport, 'quotations_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new QuotationImport($request->boolean('overwrite'));
        Excel::import($import, $request->file('file'));

        $message = "Import completed: {$import->importedCount} Quotation(s) created.";
        if ($import->skippedCount > 0) {
            $message .= " {$import->skippedCount} row(s) skipped.";
        }
        if (!empty($import->errors)) {
            $message .= ' Errors: ' . implode('; ', array_slice($import->errors, 0, 5));
        }

        return back()->with($import->importedCount > 0 ? 'success' : 'error', $message);
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\QuotationDataExport, 'quotations_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new QuotationTemplateExport, 'quotations_template.xlsx');
    }
}
