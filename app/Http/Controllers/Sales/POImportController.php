<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class POImportController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Handle the PO upload and AI processing.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB limit
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $mimeType = $file->getMimeType();

        // 1. Extract data using AI
        $extractedData = $this->gemini->extractPOData($path, $mimeType);

        if (!$extractedData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extract data from PO using AI. Please check your API configuration.'
            ], 422);
        }

        // Handle array response (if Gemini returns a list of POs, take the first one)
        if (array_key_exists(0, $extractedData) && is_array($extractedData[0])) {
            $extractedData = $extractedData[0];
        }

        // 2. Intelligent Auto-Matching
        $processedData = $this->autoMatchData($extractedData);

        return response()->json([
            'success' => true,
            'data' => $processedData
        ]);
    }

    /**
     * Match extracted text with database records.
     * Improved Logic: Checks if Product SKU exists *within* the PO Description.
     */
    private function autoMatchData(array $data): array
    {
        // Match Customer
        $customerName = $data['customer_name'] ?? '';
        $customer = Customer::where('name', 'like', "%{$customerName}%")
            ->orWhere('code', 'like', "%{$customerName}%")
            ->first();

        $data['matched_customer_id'] = $customer?->id;
        $data['matched_customer_name'] = $customer?->name;

        // Pre-fetch all active products with SKUs to avoid N+1 queries
        // This is efficient enough for < 10k products. For larger DBs, full-text search is better.
        $products = Product::whereNotNull('sku')
            ->where('sku', '!=', '')
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'selling_price']);

        // Match Products
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                $description = $item['description'] ?? '';
                $normalizedDesc = strtoupper(trim($description));
                
                $matchedProduct = null;

                // 1. Try Token-based Matching (Is SKU inside Description?)
                // Sort products by SKU length descending to match longest code first (e.g. match 'ABC-123' before 'ABC')
                foreach ($products as $product) {
                    $sku = strtoupper($product->sku);
                    // Check if SKU is a substring of description
                    if (str_contains($normalizedDesc, $sku)) {
                        $matchedProduct = $product;
                        break; // Found the best match (assuming unique SKUs)
                    }
                }

                // 2. If no SKU match, try fuzzy name match (simple contains)
                if (!$matchedProduct) {
                    $matchedProduct = Product::where('name', 'like', "%{$description}%")
                        ->active()
                        ->first();
                }

                if ($matchedProduct) {
                    // Load stock fresh
                    $matchedProduct->load('stocks');
                    
                    $item['matched_product_id'] = $matchedProduct->id;
                    $item['matched_product_name'] = $matchedProduct->name;
                    $item['matched_sku'] = $matchedProduct->sku;
                    $item['current_stock'] = $matchedProduct->total_stock ?? 0;
                    
                    $aiPrice = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
                    $dbPrice = $matchedProduct->selling_price ?? $matchedProduct->price ?? 0;
                    
                    $item['po_price'] = $aiPrice;
                    $item['db_price'] = floatval($dbPrice);
                    $item['unit_price'] = $aiPrice; 
                    $item['price_mismatch'] = $aiPrice > 0 && $dbPrice > 0 && abs($aiPrice - $dbPrice) > 0.01;
                    $item['match_status'] = 'MATCHED';
                } else {
                     $item['matched_product_id'] = null;
                     $item['match_status'] = 'NO_MATCH';
                     
                     // Use material_number from AI extraction as proposed SKU, fallback to auto-generated
                     $item['proposed_sku'] = !empty($item['material_number']) 
                         ? $item['material_number'] 
                         : $this->generateSmartSku($description);
                }
            }
        }

        return $data;
    }

    /**
     * Generate a smart SKU from description.
     * Logic:
     * 1. Look for a "code-like" word (alphanumeric, uppercase, usually at the end or distinct).
     * 2. If not found, create an abbreviation from the first letters of words.
     */
    private function generateSmartSku(string $description): string
    {
        $normalized = trim($description);
        
        // 1. Regex to find potential codes (e.g., "DXC49A", "PPD32R", "500-12")
        // Looks for words with at least 3 characters that contain:
        // - Uppercase letters and numbers
        // - OR just uppercase letters if length > 3
        // - OR numbers with dashes
        preg_match_all('/\b([A-Z0-9-]{3,})\b/', $normalized, $matches);
        
        if (!empty($matches[0])) {
            $candidates = $matches[0];
            
            // Strategy: Prioritize codes that contain BOTH letters and numbers (strongest signal)
            foreach (array_reverse($candidates) as $word) {
                if (preg_match('/[A-Z]/', $word) && preg_match('/[0-9]/', $word)) {
                    return $word;
                }
            }

            // Strategy: Failing that, if there's a code at the VERY END, it's likely the SKU
            // (e.g. "Product Name ABC")
            $lastWord = end($candidates);
            if ($lastWord) {
                 return $lastWord;
            }
            
            // Fallback to first match
            return $candidates[0];
        }

        // 2. Fallback: Abbreviation (e.g. "Pipe PVC 4 Inch" -> "PP4I")
        $words = explode(' ', $normalized);
        $sku = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $sku .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Append a random 3-digit number to avoid collisions if it's too short
        if (strlen($sku) < 3) {
            $sku .= rand(100, 999);
        }

        return substr($sku, 0, 10);
    }

    /**
     * Quick store product from PO Extractor
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'selling_price' => 'nullable|numeric|min:0',
            'type' => 'required|in:product,service,consumable',
            'product_type' => 'required|in:raw_material,wip,finished_good,spare_part',
        ]);

        // Auto-generate SKU if not provided
        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateSmartSku($validated['name']);
            
            // Check uniqueness again
            if (Product::where('sku', $validated['sku'])->exists()) {
                $validated['sku'] = $validated['sku'] . '-' . rand(10, 99);
            }
        }

        $validated['is_active'] = true;
        // ... (rest of defaults)
        $validated['is_sold'] = true;
        $validated['is_purchased'] = true;
        
        $product = Product::create($validated);
        $product->load(['stocks']);
        
        // Also create an initial stock record or logic if needed (omitted for now)
        
        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'current_stock' => $product->total_stock ?? 0,
            ],
            'message' => 'Product registered successfully'
        ]);
    }

    /**
     * Bulk store products for "Register All"
     */
    public function storeProductBulk(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.sku' => 'nullable|string|max:50',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.selling_price' => 'nullable|numeric',
        ]);

        $createdProducts = [];

        foreach ($request->items as $index => $item) {
            // Use provided SKU or auto-generate
            $sku = !empty($item['sku']) ? $item['sku'] : $this->generateSmartSku($item['description']);
             // Check uniqueness
            if (Product::where('sku', $sku)->exists()) {
                 $sku .= '-' . rand(100, 999);
            }

            $product = Product::create([
                'name' => $item['description'],
                'sku' => $sku,
                'unit_id' => $item['unit_id'],
                'selling_price' => $item['selling_price'] ?? 0,
                'type' => 'product',
                'product_type' => 'finished_good',
                'is_active' => true,
                'is_sold' => true,
                'is_purchased' => true
            ]);
            
            $product->load('stocks');

            $createdProducts[$index] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'current_stock' => $product->total_stock ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'products' => $createdProducts,
            'message' => count($createdProducts) . ' Products registered successfully'
        ]);
    }
}
