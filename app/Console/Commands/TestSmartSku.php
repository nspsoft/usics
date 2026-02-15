<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class TestSmartSku extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:smart-sku';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Smart SKU Generation and Matching Logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Running Smart SKU & Matching Logic Test");

        // 1. Setup Mock Product
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

        $this->info("[INFO] Created Product: {$product->name} (SKU: {$product->sku})");

        // 2. Test Matching Logic (Simulation)
        $description1 = "Pipe Black Steel {$testSku} High Quality";
        $matched1 = $this->simulateMatching($description1);

        if ($matched1 && $matched1->id === $product->id) {
            $this->info("[PASS] Matching Logic: Found SKU inside description '{$description1}'");
        } else {
            $this->error("[FAIL] Matching Logic: Failed to find SKU inside description '{$description1}'");
        }

        // 3. Test Smart SKU Generation
        $descriptions = [
            "Pipe PVC 4 Inch D400" => "D400", // Code at end
            "Bolt M12 x 50mm Stainless" => "M12", // Mixed alpha-num
            "Gasket SPIRAL WOUND 4 INC" => "GSW4", // Abbreviation fallback
            "Pipa Besi SCH-40 6M" => "SCH-40", // Hyphenated
            "BESI BETON 10MM FULL KSTY" => "KSTY", // Code at end text
            "BESI SIKU 40 X 40 X 4 MM (6M)" => "40X40X4MM", // Complex
        ];
        
        $this->info("\n[INFO] Testing Smart SKU Generation:");
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
        // Only checking against the created product for speed
        $products = Product::where('sku', 'like', 'TEST-%')->get(); 
        
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
        // Logic duplicated from POImportController for verification
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
