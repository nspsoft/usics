<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = PurchaseRequest::with(['createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('pr_number', 'like', "%{$search}%")
                  ->orWhere('requester', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });

        $requests = $query->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Purchasing/Requests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $prefill = null;
        
        // Handle single item
        if ($request->product_id && $request->qty) {
            $prefill = [
                'items' => [[
                    'product_id' => (int)$request->product_id,
                    'qty' => (float)$request->qty,
                    'description' => 'Reorder from Stock Recommendation',
                ]]
            ];
        }
        // Handle bulk items
        elseif ($request->products && $request->qtys && is_array($request->products)) {
            $items = [];
            foreach ($request->products as $index => $productId) {
                if (isset($request->qtys[$index])) {
                    $items[] = [
                        'product_id' => (int)$productId,
                        'qty' => (float)$request->qtys[$index],
                        'description' => 'Bulk Reorder from Stock',
                    ];
                }
            }
            if (count($items) > 0) {
                $prefill = ['items' => $items];
            }
        }

        return Inertia::render('Purchasing/Requests/Form', [
            'products' => Product::active()->where('is_purchased', true)->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'departments' => \App\Models\Department::where('is_active', true)->orderBy('name')->get(),
            'users' => \App\Models\User::orderBy('name')->get(),
            'user' => auth()->user(),
            'prefill' => $prefill,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'department' => 'required|string',
            'requester' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $pr = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generatePrNumber(),
                'request_date' => $validated['request_date'],
                'department' => $validated['department'],
                'requester' => $validated['requester'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $pr->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchasing.requests.index')
            ->with('success', 'Purchase Request created successfully.');
    }
    /**
     * Display the specified resource.
     */
    public function show(PurchaseRequest $request): Response
    {
        $request->load(['items.product', 'createdBy']);
        
        return Inertia::render('Purchasing/Requests/Show', [
            'request' => $request,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseRequest $request): Response
    {
        $request->load(['items']);
        
        return Inertia::render('Purchasing/Requests/Form', [
            'request' => $request,
            'products' => Product::active()->where('is_purchased', true)->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'departments' => \App\Models\Department::where('is_active', true)->orderBy('name')->get(),
            'users' => \App\Models\User::orderBy('name')->get(),
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'department' => 'required|string',
            'requester' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.description' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($id);

        DB::transaction(function () use ($validated, $purchaseRequest) {
            $purchaseRequest->update([
                'request_date' => $validated['request_date'],
                'department' => $validated['department'],
                'requester' => $validated['requester'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sync items
            // 1. Delete items not in request
            $purchaseRequest->items()->delete();

            // 2. Create new items (simplest approach for full sync)
            foreach ($validated['items'] as $item) {
                $purchaseRequest->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchasing.requests.index')
            ->with('success', 'Purchase Request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseRequest $request)
    {
        // Bug 6 fix: Only allow deleting draft PRs to protect audit trail
        if ($request->status !== 'draft') {
            return back()->with('error', 'Only draft purchase requests can be deleted.');
        }

        $request->delete();
        
        return redirect()->route('purchasing.requests.index')
            ->with('success', 'Purchase Request deleted successfully.');
    }

    /**
     * Duplicate the specified purchase request.
     */
    public function duplicate(PurchaseRequest $request)
    {
        $newPrId = DB::transaction(function () use ($request) {
            $request->load('items');

            // Replicate header
            $newPr = $request->replicate()->fill([
                'pr_number' => PurchaseRequest::generatePrNumber(),
                'request_date' => date('Y-m-d'),
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
            $newPr->save();

            // Replicate items
            foreach ($request->items as $item) {
                $newItem = $item->replicate()->fill([
                    'purchase_request_id' => $newPr->id,
                ]);
                $newItem->save();
            }

            return $newPr->id;
        });

        return redirect()->route('purchasing.requests.edit', $newPrId)
            ->with('success', 'Purchase Request duplicated successfully. You can now edit the new draft.');
    }

    /**
     * Approve the purchase request.
     */
    public function approve(PurchaseRequest $request)
    {
        if ($request->status !== 'draft') {
            return back()->with('error', 'Only draft requests can be approved.');
        }

        $request->update(['status' => 'approved']);

        return back()->with('success', 'Purchase Request approved.');
    }

    /**
     * Reject the purchase request.
     */
    public function reject(PurchaseRequest $request)
    {
        if ($request->status !== 'draft') {
            return back()->with('error', 'Only draft requests can be rejected.');
        }

        $request->update(['status' => 'rejected']);

        return back()->with('success', 'Purchase Request rejected.');
    }

    public function print(PurchaseRequest $request)
    {
        return view('print.purchase-request', [
            'request' => $request->load(['items.product.unit', 'createdBy'])
        ]);
    }

    public function publicValidate($id)
    {
        $request = PurchaseRequest::with(['items.product.unit', 'createdBy'])
            ->findOrFail($id);

        return view('print.public-purchase-request-validation', [
            'request' => $request
        ]);
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PurchaseRequestsExport, 'purchase_requests.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\PurchaseRequestsImport($request->boolean('overwrite')), $request->file('file'));
            return back()->with('success', 'Purchase Requests imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PurchaseRequestDataExport, 'purchase_requests_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Template\PurchaseRequestTemplateExport, 'purchase_request_template.xlsx');
    }
}
