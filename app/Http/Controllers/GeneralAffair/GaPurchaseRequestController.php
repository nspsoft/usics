<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GaPurchaseRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = PurchaseRequest::with(['createdBy'])
            ->where('department', 'HRGA')
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('pr_number', 'like', "%{$search}%")
                        ->orWhere('requester', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            });

        $requests = $query->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GeneralAffair/Requests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('GeneralAffair/Requests/Form', [
            'products' => Product::active()->where('is_purchased', true)->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
            'user' => auth()->user(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'required|date',
            'requester' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.description' => 'nullable|string',
        ]);

        $attempt = 0;
        $maxAttempts = 5;

        while (true) {
            try {
                DB::transaction(function () use ($validated) {
                    $pr = PurchaseRequest::create([
                        'company_id' => auth()->user()->company_id ?? 1,
                        'pr_number' => PurchaseRequest::generatePrNumber(),
                        'request_date' => $validated['request_date'],
                        'department' => 'HRGA',
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

                break;
            } catch (QueryException $e) {
                $errorCode = $e->errorInfo[1] ?? null;
                $message = $e->getMessage();
                $isDuplicatePrNumber = $errorCode === 1062
                    && (str_contains($message, 'purchase_requests_pr_number_unique')
                        || str_contains($message, 'Duplicate entry')
                        || str_contains($message, 'pr_number'));

                if ($isDuplicatePrNumber && $attempt < ($maxAttempts - 1)) {
                    $attempt++;
                    usleep(200000 * $attempt);
                    continue;
                }

                throw $e;
            }
        }

        return redirect()->route('ga.requests.index')
            ->with('success', 'Purchase Request created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): Response
    {
        $request = PurchaseRequest::where('department', 'HRGA')
            ->with(['items.product.unit', 'createdBy'])
            ->findOrFail($id);
        
        return Inertia::render('GeneralAffair/Requests/Show', [
            'request' => $request,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): Response
    {
        $request = PurchaseRequest::where('department', 'HRGA')
            ->with(['items'])
            ->findOrFail($id);
        
        return Inertia::render('GeneralAffair/Requests/Form', [
            'request' => $request,
            'products' => Product::active()->where('is_purchased', true)->select('id','sku','name','unit_id','cost_price')->with('unit:id,name,symbol')->orderBy('name')->get()->each->setAppends([]),
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
            'requester' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.description' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::where('department', 'HRGA')->findOrFail($id);

        DB::transaction(function () use ($validated, $purchaseRequest) {
            $purchaseRequest->update([
                'request_date' => $validated['request_date'],
                'requester' => $validated['requester'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Sync items
            $purchaseRequest->items()->delete();

            foreach ($validated['items'] as $item) {
                $purchaseRequest->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('ga.requests.index')
            ->with('success', 'Purchase Request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::where('department', 'HRGA')->findOrFail($id);

        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'Only draft purchase requests can be deleted.');
        }

        $purchaseRequest->delete();
        
        return redirect()->route('ga.requests.index')
            ->with('success', 'Purchase Request deleted successfully.');
    }
}
