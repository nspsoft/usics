<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SmartSkuTest extends TestCase
{
    // Use RefreshDatabase to reset DB after test
    // use RefreshDatabase; 

    public function test_smart_sku_generation_and_matching()
    {
        echo "\nRunning Smart SKU & Matching Logic Test\n";
        
        // 1. Setup Mock Products
        // Create a product with SKU "ABC-123"
        // We will test if description "Product Name ABC-123" matches this.
        
        $testSku = "TEST-999-" . rand(100,999);
        $product = Product::create([
            'name' => 'Existing Product',
            'sku' => $testSku,
            'unit_id' => 1, 
            'type' => 'product',
            'product_type' => 'finished_good',
            'selling_price' => 100000,
            'is_active' => true,
            'is_sold' => true,
            'is_purchased' => true
        ]);
        
        echo "[INFO] Created Product: {$product->name} (SKU: {$product->sku})\n";

        // 2. Test Matching Logic (Simulation of autoMatchData)
        // Description contains SKU
        $description1 = "Pipe Black Steel {$testSku} High Quality";
        $matched1 = $this->simulateMatching($description1);
        
        if ($matched1 && $matched1->id === $product->id) {
            echo "[PASS] Matching Logic: Found SKU inside description '{$description1}'\n";
        } else {
            echo "[FAIL] Matching Logic: Failed to find SKU inside description '{$description1}'\n";
        }

        // 3. Test Smart SKU Generation
        $descriptions = [
            "Pipe PVC 4 Inch D400" => "D400", // Code at end
            "Bolt M12 x 50mm Stainless" => "M12", // Mixed alpha-num
            "Gasket SPIRAL WOUND 4 INC" => "GSW4", // Abbreviation fallback (approx)
            "Pipa Besi SCH-40 6M" => "SCH-40", // Hyphenated
        ];
        
        echo "\n[INFO] Testing Smart SKU Generation:\n";
        foreach ($descriptions as $desc => $expected) {
            $generated = $this->generateSmartSku($desc);
            echo "  Input: '{$desc}' => Generated: '{$generated}'\n";
        }

        // Cleanup
        $product->delete();
    }

    private function simulateMatching($description)
    {
        $normalizedDesc = strtoupper(trim($description));
        // Simple simulation of the controller logic
        $products = Product::where('id', '>', 0)->get(); // Get all for test (in real app we filter)
        
        foreach ($products as $product) {
            $sku = strtoupper($product->sku);
            if (!empty($sku) && str_contains($normalizedDesc, $sku)) {
                return $product;
            }
        }
        return null;
    }

    private function generateSmartSku(string $description): string
    {
        $normalized = trim($description);
        
        // 1. Regex to find potential codes
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
            $lastWord = end($candidates);
            if ($lastWord) {
                 return $lastWord;
            }
            
            return $candidates[0];
        }

        // 2. Fallback: Abbreviation
        $words = explode(' ', $normalized);
        $sku = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $sku .= strtoupper(substr($word, 0, 1));
            }
        }
        
        if (strlen($sku) < 3) {
            $sku .= rand(100, 999);
        }

        return substr($sku, 0, 10);
    }
}

// Run the test manually
$test = new SmartSkuTest();
$test->test_smart_sku_generation_and_matching();
