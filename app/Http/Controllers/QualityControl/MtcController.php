<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use App\Models\MtcDocument;
use App\Models\MtcItem;
use App\Models\InventoryLot;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Location;
use App\Services\MtcExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MtcController extends Controller
{
    protected MtcExtractionService $extractionService;

    public function __construct(MtcExtractionService $extractionService)
    {
        $this->extractionService = $extractionService;
    }

    /**
     * Display MTC documents dashboard.
     */
    public function index(Request $request)
    {
        $query = MtcDocument::with(['creator', 'verifier', 'supplier'])
            ->withCount('items');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier')) {
            $query->where('supplier_name', 'like', '%' . $request->supplier . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('po_no', 'like', "%{$search}%")
                  ->orWhere('order_no', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderByDesc('created_at')->paginate(15);

        // Stats
        $stats = [
            'total' => MtcDocument::count(),
            'draft' => MtcDocument::where('status', 'draft')->count(),
            'verified' => MtcDocument::where('status', 'verified')->count(),
            'rejected' => MtcDocument::where('status', 'rejected')->count(),
        ];

        return Inertia::render('QualityControl/Mtc/Index', [
            'documents' => $documents,
            'stats' => $stats,
            'filters' => $request->only(['status', 'supplier', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Show the upload page.
     */
    public function create()
    {
        return Inertia::render('QualityControl/Mtc/Create');
    }

    /**
     * Upload and extract MTC document with AI.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480', // 20MB
                'mimes:pdf,jpg,jpeg,png,webp,tiff',
            ],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        // Determine file type
        $fileType = str_starts_with($mimeType, 'image/') ? 'image' : 'pdf';

        // Store the file privatly
        $path = $file->store('mtc_documents', 'local');

        // Run AI extraction
        $result = $this->extractionService->extract($path, $mimeType);

        if (!$result['success']) {
            // Save document as draft even if extraction fails so user can manually fill
            $document = MtcDocument::create([
                'file_path' => $path,
                'file_name' => $originalName,
                'file_type' => $fileType,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => 'AI extraction failed: ' . ($result['error'] ?? 'Unknown error'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload berhasil tapi ekstraksi AI gagal: ' . ($result['error'] ?? 'Unknown error'),
                'document_id' => $document->id,
                'redirect' => route('qc.mtc.show', $document),
            ], 200); // 200 to allow Inertia handling of redirects on frontend
        }

        // Save document and items in a transaction
        $document = DB::transaction(function () use ($path, $originalName, $fileType, $result) {
            $data = $result['data'];
            $header = $data['header'] ?? [];

            // Attempt to map supplier name to supplier_id
            $supplierId = null;
            if (!empty($header['supplier_name'])) {
                $supplier = Supplier::where('name', 'like', '%' . $header['supplier_name'] . '%')->first();
                if ($supplier) {
                    $supplierId = $supplier->id;
                }
            }

            // Create main document
            $document = MtcDocument::create([
                'file_path' => $path,
                'file_name' => $originalName,
                'file_type' => $fileType,
                'supplier_id' => $supplierId,
                'supplier_name' => $header['supplier_name'] ?? null,
                'certificate_number' => $header['certificate_number'] ?? null,
                'date_of_issue' => $this->parseDate($header['date_of_issue'] ?? null),
                'order_no' => $header['order_no'] ?? null,
                'po_no' => $header['po_no'] ?? null,
                'commodity' => $header['commodity'] ?? null,
                'spec_and_type' => $header['spec_and_type'] ?? null,
                'customer' => $header['customer'] ?? null,
                'raw_ai_response' => $result['raw_response'],
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Create items
            $items = $data['items'] ?? [];
            foreach ($items as $itemData) {
                // Parse dimensions from size string
                $size = $itemData['size'] ?? null;
                $product_id = null;

                // Simple size-based product lookup if available
                if ($size) {
                    $dimensions = $this->parseDimensions($size);
                    $productQuery = Product::query();
                    if ($dimensions['thickness']) {
                        $productQuery->where('attributes->thickness', $dimensions['thickness']);
                    }
                    if ($dimensions['width']) {
                        $productQuery->where('width', $dimensions['width']);
                    }
                    $matchedProduct = $productQuery->first();
                    if ($matchedProduct) {
                        $product_id = $matchedProduct->id;
                    }
                }

                    // Mechanical properties fallback (can be directly or nested under tensile_test)
                    $yp = $itemData['yp_mpa'] ?? $itemData['tensile_test']['yp_mpa'] ?? null;
                    $ts = $itemData['ts_mpa'] ?? $itemData['tensile_test']['ts_mpa'] ?? null;
                    $el = $itemData['el_percent'] ?? $itemData['tensile_test']['el_percent'] ?? null;
                    $yr = $itemData['yr_percent'] ?? $itemData['tensile_test']['yr_percent'] ?? null;

                    // Chemical ladle and product fallback
                    $chemLadle = $itemData['chemical_ladle'] ?? null;
                    $chemProduct = $itemData['chemical_product'] ?? null;

                    // If chemical_composition is returned as an array (e.g. from OpenRouter/Vision models)
                    if (isset($itemData['chemical_composition']) && is_array($itemData['chemical_composition'])) {
                        // Check if it's a list of division arrays (e.g. [['division' => 'L', 'C' => 0.1], ...])
                        $isList = true;
                        foreach ($itemData['chemical_composition'] as $k => $v) {
                            if (!is_int($k) || !is_array($v)) {
                                $isList = false;
                                break;
                            }
                        }

                        if ($isList) {
                            foreach ($itemData['chemical_composition'] as $comp) {
                                if (is_array($comp)) {
                                    $div = strtoupper($comp['division'] ?? '');
                                    $cleanedComp = [];
                                    foreach ($comp as $key => $val) {
                                        if ($key === 'division') continue;
                                        $elemName = str_replace('_percent', '', $key);
                                        $cleanedComp[$elemName] = $val;
                                    }
                                    
                                    if ($div === 'L' || $div === 'LADLE') {
                                        $chemLadle = array_merge($chemLadle ?? [], $cleanedComp);
                                    } elseif ($div === 'P' || $div === 'PRODUCT') {
                                        $chemProduct = array_merge($chemProduct ?? [], $cleanedComp);
                                    }
                                }
                            }
                        } else {
                            // Flat associative array (e.g. ['C' => 0.1, 'Si' => 0.2])
                            $cleanedComp = [];
                            foreach ($itemData['chemical_composition'] as $key => $val) {
                                $elemName = str_replace('_percent', '', $key);
                                $cleanedComp[$elemName] = $val;
                            }
                            $chemLadle = array_merge($chemLadle ?? [], $cleanedComp);
                        }
                    }

                    MtcItem::create([
                        'mtc_document_id' => $document->id,
                        'product_id' => $product_id,
                        'product_no' => $itemData['product_no'] ?? null,
                        'heat_no' => $itemData['heat_no'] ?? null,
                        'size' => $size,
                        'quantity' => $itemData['quantity'] ?? null,
                        'weight_kg' => $itemData['weight_kg'] ?? null,
                        'position' => $itemData['position'] ?? null,
                        'yp_mpa' => $yp,
                        'ts_mpa' => $ts,
                        'el_percent' => $el,
                        'yr_percent' => $yr,
                        'bend_test' => $itemData['bend_test'] ?? null,
                        'impact_test_data' => $itemData['impact_test_data'] ?? null,
                        'chemical_ladle' => $chemLadle,
                        'chemical_product' => $chemProduct,
                        'compliance_status' => 'unchecked',
                    ]);
                }

            return $document;
        });

        return response()->json([
            'success' => true,
            'message' => 'MTC berhasil diekstrak! Menemukan ' . $document->items()->count() . ' coil.',
            'document_id' => $document->id,
            'redirect' => route('qc.mtc.show', $document),
            'confidence' => $result['data']['confidence_score'] ?? null,
        ]);
    }

    /**
     * Show the verification console (split-pane view).
     */
    public function show(MtcDocument $document)
    {
        $document->load(['items.product', 'creator', 'verifier', 'supplier']);

        return Inertia::render('QualityControl/Mtc/Show', [
            'document' => $document,
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Update document and items data (from verification console).
     */
    public function update(Request $request, MtcDocument $document)
    {
        if (!$document->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen ini sudah diverifikasi dan tidak dapat diedit.',
            ], 403);
        }

        DB::transaction(function () use ($request, $document) {
            // Update header
            $document->update([
                'supplier_id' => $request->input('supplier_id'),
                'supplier_name' => $request->input('supplier_name'),
                'certificate_number' => $request->input('certificate_number'),
                'date_of_issue' => $request->input('date_of_issue'),
                'order_no' => $request->input('order_no'),
                'po_no' => $request->input('po_no'),
                'commodity' => $request->input('commodity'),
                'spec_and_type' => $request->input('spec_and_type'),
                'customer' => $request->input('customer'),
                'notes' => $request->input('notes'),
            ]);

            // Update items
            $items = $request->input('items', []);
            foreach ($items as $itemData) {
                if (isset($itemData['id'])) {
                    $item = MtcItem::where('id', $itemData['id'])
                        ->where('mtc_document_id', $document->id)
                        ->first();

                    if ($item) {
                        $item->update([
                            'product_id' => $itemData['product_id'] ?? null,
                            'product_no' => $itemData['product_no'] ?? null,
                            'heat_no' => $itemData['heat_no'] ?? null,
                            'size' => $itemData['size'] ?? null,
                            'quantity' => $itemData['quantity'] ?? null,
                            'weight_kg' => $itemData['weight_kg'] ?? null,
                            'yp_mpa' => $itemData['yp_mpa'] ?? null,
                            'ts_mpa' => $itemData['ts_mpa'] ?? null,
                            'el_percent' => $itemData['el_percent'] ?? null,
                            'yr_percent' => $itemData['yr_percent'] ?? null,
                            'bend_test' => $itemData['bend_test'] ?? null,
                            'chemical_ladle' => $itemData['chemical_ladle'] ?? null,
                            'chemical_product' => $itemData['chemical_product'] ?? null,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil disimpan.',
        ]);
    }

    /**
     * Verify a document (mark as verified and create inventory lots).
     */
    public function verify(Request $request, MtcDocument $document)
    {
        if ($document->status !== 'draft') {
            return back()->with('error', 'Hanya dokumen draft yang dapat diverifikasi.');
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:locations,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
        ]);

        DB::transaction(function () use ($request, $document) {
            // 1. Update header
            $document->update([
                'supplier_id' => $request->input('supplier_id'),
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'notes' => $request->input('notes', $document->notes),
            ]);

            // 2. Update and process items
            $itemsData = $request->input('items', []);
            foreach ($itemsData as $itemData) {
                $item = MtcItem::where('id', $itemData['id'])
                    ->where('mtc_document_id', $document->id)
                    ->first();

                if ($item) {
                    // Update item reference
                    $item->update([
                        'product_id' => $itemData['product_id'],
                        'product_no' => $itemData['product_no'],
                        'heat_no' => $itemData['heat_no'],
                        'weight_kg' => $itemData['weight_kg'],
                        'size' => $itemData['size'],
                    ]);

                    // Parse thickness and width from size
                    $dimensions = $this->parseDimensions($itemData['size']);

                    // Create or update InventoryLot for each coil
                    InventoryLot::updateOrCreate(
                        ['coil_number' => $itemData['product_no']],
                        [
                            'product_id' => $itemData['product_id'],
                            'warehouse_id' => $request->input('warehouse_id'),
                            'location_id' => $request->input('location_id'),
                            'heat_number' => $itemData['heat_no'],
                            'mill_id' => $request->input('supplier_id'),
                            'thickness' => $dimensions['thickness'],
                            'width' => $dimensions['width'],
                            'weight' => $itemData['weight_kg'],
                            'qty' => $itemData['quantity'] ?? 1.0,
                            'status' => 'available',
                            'mtc_document_id' => $document->id,
                            'notes' => 'Imported via MTC AI Extractor Cert No: ' . $document->certificate_number,
                        ]
                    );
                }
            }
        });

        return redirect()->route('qc.mtc.index')->with('success', 'MTC Dokumen berhasil diverifikasi dan coil telah masuk inventaris.');
    }

    /**
     * Reject a document.
     */
    public function reject(Request $request, MtcDocument $document)
    {
        $document->update([
            'status' => 'rejected',
            'notes' => $request->input('reason', 'Ditolak oleh ' . Auth::user()->name),
        ]);

        return redirect()->route('qc.mtc.index')->with('success', 'MTC Dokumen telah ditolak.');
    }

    /**
     * Delete a document.
     */
    public function destroy(MtcDocument $document)
    {
        // Delete file from storage
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete(); // Items cascade deleted via foreign key

        return redirect()->route('qc.mtc.index')->with('success', 'MTC Dokumen berhasil dihapus.');
    }

    /**
     * Serve the MTC file for viewing in the browser.
     */
    public function viewFile(MtcDocument $document)
    {
        $fullPath = Storage::disk('local')->path($document->file_path);

        if (!file_exists($fullPath)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $mimeType = $document->file_type === 'pdf' ? 'application/pdf' : mime_content_type($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
        ]);
    }

    /**
     * Re-extract data from existing document using AI.
     */
    public function reExtract(MtcDocument $document)
    {
        $mimeType = $document->file_type === 'pdf' ? 'application/pdf' : 'image/jpeg';

        if ($document->file_type === 'image') {
            $ext = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'tiff', 'tif' => 'image/tiff',
                default => 'image/jpeg',
            };
        }

        $result = $this->extractionService->extract($document->file_path, $mimeType);

        if (!$result['success']) {
            return back()->with('error', 'Re-ekstraksi gagal: ' . ($result['error'] ?? 'Unknown error'));
        }

        DB::transaction(function () use ($document, $result) {
            $data = $result['data'];
            $header = $data['header'] ?? [];

            // Update header
            $document->update([
                'supplier_name' => $header['supplier_name'] ?? $document->supplier_name,
                'certificate_number' => $header['certificate_number'] ?? $document->certificate_number,
                'date_of_issue' => $this->parseDate($header['date_of_issue'] ?? null) ?? $document->date_of_issue,
                'order_no' => $header['order_no'] ?? $document->order_no,
                'po_no' => $header['po_no'] ?? $document->po_no,
                'commodity' => $header['commodity'] ?? $document->commodity,
                'spec_and_type' => $header['spec_and_type'] ?? $document->spec_and_type,
                'customer' => $header['customer'] ?? $document->customer,
                'raw_ai_response' => $result['raw_response'],
                'notes' => $data['notes'] ?? $document->notes,
            ]);

            // Re-create items (delete old ones and create new ones)
            $document->items()->delete();

            $items = $data['items'] ?? [];
            foreach ($items as $itemData) {
                $size = $itemData['size'] ?? null;
                $product_id = null;

                if ($size) {
                    $dimensions = $this->parseDimensions($size);
                    $productQuery = Product::query();
                    if ($dimensions['thickness']) {
                        $productQuery->where('attributes->thickness', $dimensions['thickness']);
                    }
                    if ($dimensions['width']) {
                        $productQuery->where('width', $dimensions['width']);
                    }
                    $matchedProduct = $productQuery->first();
                    if ($matchedProduct) {
                        $product_id = $matchedProduct->id;
                    }
                }

                    // Mechanical properties fallback (can be directly or nested under tensile_test)
                    $yp = $itemData['yp_mpa'] ?? $itemData['tensile_test']['yp_mpa'] ?? null;
                    $ts = $itemData['ts_mpa'] ?? $itemData['tensile_test']['ts_mpa'] ?? null;
                    $el = $itemData['el_percent'] ?? $itemData['tensile_test']['el_percent'] ?? null;
                    $yr = $itemData['yr_percent'] ?? $itemData['tensile_test']['yr_percent'] ?? null;

                    // Chemical ladle and product fallback
                    $chemLadle = $itemData['chemical_ladle'] ?? null;
                    $chemProduct = $itemData['chemical_product'] ?? null;

                    // If chemical_composition is returned as an array (e.g. from OpenRouter/Vision models)
                    if (isset($itemData['chemical_composition']) && is_array($itemData['chemical_composition'])) {
                        // Check if it's a list of division arrays (e.g. [['division' => 'L', 'C' => 0.1], ...])
                        $isList = true;
                        foreach ($itemData['chemical_composition'] as $k => $v) {
                            if (!is_int($k) || !is_array($v)) {
                                $isList = false;
                                break;
                            }
                        }

                        if ($isList) {
                            foreach ($itemData['chemical_composition'] as $comp) {
                                if (is_array($comp)) {
                                    $div = strtoupper($comp['division'] ?? '');
                                    $cleanedComp = [];
                                    foreach ($comp as $key => $val) {
                                        if ($key === 'division') continue;
                                        $elemName = str_replace('_percent', '', $key);
                                        $cleanedComp[$elemName] = $val;
                                    }
                                    
                                    if ($div === 'L' || $div === 'LADLE') {
                                        $chemLadle = array_merge($chemLadle ?? [], $cleanedComp);
                                    } elseif ($div === 'P' || $div === 'PRODUCT') {
                                        $chemProduct = array_merge($chemProduct ?? [], $cleanedComp);
                                    }
                                }
                            }
                        } else {
                            // Flat associative array (e.g. ['C' => 0.1, 'Si' => 0.2])
                            $cleanedComp = [];
                            foreach ($itemData['chemical_composition'] as $key => $val) {
                                $elemName = str_replace('_percent', '', $key);
                                $cleanedComp[$elemName] = $val;
                            }
                            $chemLadle = array_merge($chemLadle ?? [], $cleanedComp);
                        }
                    }

                    MtcItem::create([
                        'mtc_document_id' => $document->id,
                        'product_id' => $product_id,
                        'product_no' => $itemData['product_no'] ?? null,
                        'heat_no' => $itemData['heat_no'] ?? null,
                        'size' => $size,
                        'quantity' => $itemData['quantity'] ?? null,
                        'weight_kg' => $itemData['weight_kg'] ?? null,
                        'yp_mpa' => $yp,
                        'ts_mpa' => $ts,
                        'el_percent' => $el,
                        'yr_percent' => $yr,
                        'bend_test' => $itemData['bend_test'] ?? null,
                        'chemical_ladle' => $chemLadle,
                        'chemical_product' => $chemProduct,
                        'compliance_status' => 'unchecked',
                    ]);
                }
        });

        return back()->with('success', 'Data MTC berhasil diekstrak ulang oleh AI.');
    }

    /**
     * Helper to parse size string e.g. "6.02x1045xC" or "6.0*1219" into thickness and width.
     */
    private function parseDimensions(?string $size): array
    {
        if (!$size) {
            return ['thickness' => null, 'width' => null];
        }

        $size = str_replace(' ', '', $size);
        $parts = preg_split('/[xX*]/', $size);

        $thickness = isset($parts[0]) && is_numeric($parts[0]) ? (float) $parts[0] : null;
        $width = isset($parts[1]) && is_numeric($parts[1]) ? (float) $parts[1] : null;

        return [
            'thickness' => $thickness,
            'width' => $width,
        ];
    }

    /**
     * Helper to parse date string into Carbon-friendly format.
     */
    private function parseDate(?string $dateString): ?string
    {
        if (!$dateString) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Quick register a new supplier from MTC console.
     */
    public function quickCreateSupplier(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = $request->input('name');
        
        // Generate unique code based on prefix and initials
        $cleanName = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $prefix = 'SPL-' . substr($cleanName, 0, 8);
        
        $code = $prefix;
        $counter = 1;
        while (\App\Models\Supplier::where('code', $code)->exists()) {
            $code = $prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        $supplier = \App\Models\Supplier::create([
            'company_id' => \App\Models\Company::first()?->id ?? 1,
            'code' => $code,
            'name' => $name,
            'payment_terms' => 'COD',
            'payment_days' => 0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'supplier' => $supplier,
        ]);
    }
}
