<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceSparepartController extends Controller
{
    public function index()
    {
        $spareparts = Sparepart::orderBy('name', 'asc')->get()->map(function ($part) {
            // 1. Calculate Consumption in the last 30 days
            $usage30d = DB::table('maintenance_sparepart_usage')
                ->join('maintenance_logs', 'maintenance_sparepart_usage.maintenance_log_id', '=', 'maintenance_logs.id')
                ->where('maintenance_sparepart_usage.sparepart_id', $part->id)
                ->where('maintenance_logs.started_at', '>=', Carbon::now()->subDays(30))
                ->sum('maintenance_sparepart_usage.qty_used') ?? 0;

            $usage30d = (int) $usage30d;

            // 2. Burn rate per day
            $burnRate = $usage30d / 30.0;

            // 3. Estimated Runout Days / Date
            if ($burnRate > 0) {
                $runoutDays = (int) ceil($part->stock / $burnRate);
                $runoutDate = Carbon::today()->addDays($runoutDays)->format('d M Y');
                $runoutText = "{$runoutDays} hari ({$runoutDate})";
            } else {
                $runoutDays = null;
                $runoutText = 'Aman (Tidak ada pemakaian)';
            }

            // 4. Status Stok & Warna
            $status = 'Aman';
            $statusColor = 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';

            if ($part->stock == 0) {
                $status = 'Habis';
                $statusColor = 'text-rose-400 bg-rose-500/10 border-rose-500/20 animate-pulse font-bold';
            } elseif ($part->stock <= $part->min_stock || ($runoutDays !== null && $runoutDays <= 7)) {
                $status = 'Kritis';
                $statusColor = 'text-rose-400 bg-rose-500/10 border-rose-500/20 font-bold';
            } elseif ($part->stock <= $part->min_stock * 1.5 || ($runoutDays !== null && $runoutDays <= 15)) {
                $status = 'Warning';
                $statusColor = 'text-amber-400 bg-amber-500/10 border-amber-500/20 font-bold';
            }

            // 5. Check if this sparepart already has a pending/draft PR
            $pendingPr = PurchaseRequest::whereIn('status', ['draft', 'pending'])
                ->where('notes', 'like', "%Generated for Sparepart: {$part->part_number}%")
                ->orWhere(function ($q) use ($part) {
                    $q->whereIn('status', ['draft', 'pending'])
                      ->where('notes', 'like', "%Consolidated Auto-PR for Spareparts:%{$part->part_number}%");
                })
                ->first();

            return [
                'id' => $part->id,
                'name' => $part->name,
                'part_number' => $part->part_number ?? '-',
                'location' => $part->location ?? '-',
                'stock' => $part->stock,
                'min_stock' => $part->min_stock,
                'cost' => 'Rp ' . number_format($part->unit_cost, 0, ',', '.'),
                'cost_raw' => (float) $part->unit_cost,
                'usage_30d' => $usage30d,
                'runout_days' => $runoutDays,
                'runout_text' => $runoutText,
                'status' => $status,
                'status_color' => $statusColor,
                'has_pending_pr' => $pendingPr !== null,
                'pending_pr_number' => $pendingPr?->pr_number,
            ];
        });

        return Inertia::render('Maintenance/Spareparts', [
            'spareparts' => $spareparts,
            'stats' => [
                'total_items' => $spareparts->count(),
                'low_stock' => $spareparts->filter(fn($p) => $p['status'] === 'Kritis' || $p['status'] === 'Habis')->count(),
                'stock_value' => 'Rp ' . number_format(Sparepart::sum(DB::raw('stock * unit_cost')), 0, ',', '.'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'location' => 'nullable|string',
        ]);

        Sparepart::create($validated);

        return redirect()->back()->with('success', 'Sparepart Added');
    }

    public function update(Request $request, Sparepart $sparepart)
    {
        // Simple stock adjustment
        if ($request->has('adjustment')) {
            $sparepart->increment('stock', $request->adjustment);
        } else {
            $sparepart->update($request->validate([
                'name' => 'required|string',
                'stock' => 'required|integer',
                'min_stock' => 'required|integer',
                'unit_cost' => 'required|numeric|min:0',
                'location' => 'nullable|string',
            ]));
        }

        return redirect()->back()->with('success', 'Sparepart Updated');
    }

    /**
     * Generate automatic Purchase Request (Auto-PR) in Purchasing module.
     */
    public function generateAutoPr(Request $request, Sparepart $sparepart)
    {
        // Check if a draft/pending PR for this sparepart already exists (single or consolidated)
        $existingPr = PurchaseRequest::whereIn('status', ['draft', 'pending'])
            ->where(function ($query) use ($sparepart) {
                $query->where('notes', 'like', "%Generated for Sparepart: {$sparepart->part_number}%")
                      ->orWhere('notes', 'like', "%Consolidated Auto-PR for Spareparts:%{$sparepart->part_number}%");
            })
            ->first();

        if ($existingPr) {
            return redirect()->back()->with('warning', "PR untuk sparepart ini sudah aktif: {$existingPr->pr_number} (status: {$existingPr->status}). Selesaikan atau batalkan PR tersebut terlebih dahulu.");
        }

        // 1. Find or create a matching Product in inventory
        $product = Product::where('sku', $sparepart->part_number)->first();
        
        if (!$product) {
            $category = Category::where('code', 'SP')->first() ?? Category::first();
            $unit = Unit::where('code', 'PCS')->first() ?? Unit::first();
            
            $product = Product::create([
                'company_id' => session('company_id') ?? 1,
                'sku' => $sparepart->part_number ?? ('SP-' . str_pad($sparepart->id, 5, '0', STR_PAD_LEFT)),
                'name' => $sparepart->name,
                'category_id' => $category?->id,
                'unit_id' => $unit?->id,
                'product_type' => 'spare_part',
                'cost_price' => $sparepart->unit_cost,
                'is_purchased' => true,
                'is_active' => true,
            ]);
        }

        // 2. Create Purchase Request (draft)
        $prNumber = PurchaseRequest::generatePrNumber();
        
        DB::transaction(function () use ($sparepart, $product, $prNumber) {
            $pr = PurchaseRequest::create([
                'company_id' => session('company_id') ?? 1,
                'pr_number' => $prNumber,
                'department' => 'Maintenance',
                'requester' => auth()->user()->name ?? 'System',
                'request_date' => Carbon::today(),
                'status' => 'draft',
                'notes' => "Generated for Sparepart: {$sparepart->part_number}. Auto-reorder based on forecasting. Current stock: {$sparepart->stock}, min limit: {$sparepart->min_stock}.",
                'created_by' => auth()->id(),
            ]);

            // Reorder formula: min_stock * 2 - current_stock
            $reorderQty = max(1, ($sparepart->min_stock * 2) - $sparepart->stock);

            PurchaseRequestItem::create([
                'purchase_request_id' => $pr->id,
                'product_id' => $product->id,
                'qty' => $reorderQty,
                'description' => "Reorder otomatis suku cadang pemeliharaan mesin: {$sparepart->name}",
            ]);
        });

        return redirect()->back()->with('success', "Draft PR {$prNumber} berhasil dibuat di modul Purchasing.");
    }

    /**
     * Generate automatic consolidated Purchase Request (Auto-PR) for multiple selected spareparts.
     */
    public function generateAutoPrBulk(Request $request)
    {
        $validated = $request->validate([
            'sparepart_ids' => 'required|array',
            'sparepart_ids.*' => 'exists:spareparts,id',
        ]);

        $spareparts = Sparepart::whereIn('id', $validated['sparepart_ids'])->get();

        if ($spareparts->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada suku cadang yang dipilih.');
        }

        // Filter out spareparts that already have active (draft/pending) PRs
        $skippedParts = collect();
        $eligibleParts = $spareparts->filter(function ($sp) use (&$skippedParts) {
            $existingPr = PurchaseRequest::whereIn('status', ['draft', 'pending'])
                ->where(function ($query) use ($sp) {
                    $query->where('notes', 'like', "%Generated for Sparepart: {$sp->part_number}%")
                          ->orWhere('notes', 'like', "%Consolidated Auto-PR for Spareparts:%{$sp->part_number}%");
                })
                ->first();

            if ($existingPr) {
                $skippedParts->push("{$sp->name} ({$existingPr->pr_number})");
                return false;
            }
            return true;
        });

        // If all items are already covered by existing PRs
        if ($eligibleParts->isEmpty()) {
            $skippedList = $skippedParts->implode(', ');
            return redirect()->back()->with('warning', "Semua suku cadang yang dipilih sudah memiliki PR aktif: {$skippedList}. Selesaikan atau batalkan PR tersebut terlebih dahulu.");
        }

        // Generate a single consolidated Purchase Request (draft)
        $prNumber = PurchaseRequest::generatePrNumber();
        
        DB::transaction(function () use ($eligibleParts, $prNumber) {
            $notesParts = $eligibleParts->map(fn($sp) => $sp->part_number)->implode(', ');
            
            $pr = PurchaseRequest::create([
                'company_id' => session('company_id') ?? 1,
                'pr_number' => $prNumber,
                'department' => 'Maintenance',
                'requester' => auth()->user()->name ?? 'System',
                'request_date' => Carbon::today(),
                'status' => 'draft',
                'notes' => "Consolidated Auto-PR for Spareparts: {$notesParts}. Auto-reorder based on forecasting.",
                'created_by' => auth()->id(),
            ]);

            foreach ($eligibleParts as $sparepart) {
                // Find or create a matching Product in inventory
                $product = Product::where('sku', $sparepart->part_number)->first();
                
                if (!$product) {
                    $category = Category::where('code', 'SP')->first() ?? Category::first();
                    $unit = Unit::where('code', 'PCS')->first() ?? Unit::first();
                    
                    $product = Product::create([
                        'company_id' => session('company_id') ?? 1,
                        'sku' => $sparepart->part_number ?? ('SP-' . str_pad($sparepart->id, 5, '0', STR_PAD_LEFT)),
                        'name' => $sparepart->name,
                        'category_id' => $category?->id,
                        'unit_id' => $unit?->id,
                        'product_type' => 'spare_part',
                        'cost_price' => $sparepart->unit_cost,
                        'is_purchased' => true,
                        'is_active' => true,
                    ]);
                }

                // Reorder formula: min_stock * 2 - current_stock
                $reorderQty = max(1, ($sparepart->min_stock * 2) - $sparepart->stock);

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $product->id,
                    'qty' => $reorderQty,
                    'description' => "Reorder otomatis suku cadang pemeliharaan mesin: {$sparepart->name}",
                ]);
            }
        });

        // Build success message with skipped info
        $msg = "Draft PR Konsolidasi {$prNumber} berisi " . $eligibleParts->count() . " item berhasil dibuat di modul Purchasing.";
        if ($skippedParts->isNotEmpty()) {
            $msg .= " (Dilewati karena sudah punya PR aktif: " . $skippedParts->implode(', ') . ")";
        }

        return redirect()->back()->with('success', $msg);
    }
}
