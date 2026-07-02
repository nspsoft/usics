import re

with open('c:\\laragon\\www\\USICS\\app\\Http\\Controllers\\Manufacturing\\BomController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace validation rules in store method
old_validation_store = """            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.qty_ratio' => 'required|numeric|min:0.0001',
            'outputs.*.unit_id' => 'required|exists:units,id',
            'outputs.*.notes' => 'nullable|string',
        ]);"""

new_validation_store = """            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.qty_ratio' => 'required|numeric|min:0.0001',
            'outputs.*.slit_count' => 'nullable|integer|min:1',
            'outputs.*.unit_id' => 'required|exists:units,id',
            'outputs.*.notes' => 'nullable|string',
        ]);
        
        // --- 1% Waste Limit Validation for Slitting ---
        if (!empty($validated['outputs'])) {
            $mainProduct = \App\Models\Product::find($validated['product_id']);
            if ($mainProduct && $mainProduct->width > 0) {
                $motherCoilWidth = $mainProduct->width;
                $totalOutputWidth = 0;
                foreach ($validated['outputs'] as $out) {
                    $outProduct = \App\Models\Product::find($out['product_id']);
                    if ($outProduct && $outProduct->width > 0) {
                        $slits = isset($out['slit_count']) ? (int)$out['slit_count'] : 1;
                        $totalOutputWidth += ($outProduct->width * $slits);
                    }
                }
                
                // Only validate if outputs have width (to prevent breaking non-slitting BOMs)
                if ($totalOutputWidth > 0) {
                    $waste = $motherCoilWidth - $totalOutputWidth;
                    $wastePercentage = ($waste / $motherCoilWidth) * 100;
                    
                    if ($wastePercentage > 1.0) {
                        return back()->withErrors([
                            'outputs' => sprintf("Total waste (scrap) melebihi batas 1%% (Lebar Waste: %.1f mm / %.2f%%). Silakan optimalkan kombinasi potong.", $waste, $wastePercentage)
                        ])->withInput();
                    } elseif ($waste < 0) {
                        return back()->withErrors([
                            'outputs' => sprintf("Total lebar Baby Coils melebihi Mother Coil (Lebar Output: %.1f mm vs Mother: %.1f mm).", $totalOutputWidth, $motherCoilWidth)
                        ])->withInput();
                    }
                }
            }
        }
        // ----------------------------------------------"""
        
content = content.replace(old_validation_store, new_validation_store, 1)

# Do the same for update method
old_validation_update = """            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.qty_ratio' => 'required|numeric|min:0.0001',
            'outputs.*.unit_id' => 'required|exists:units,id',
            'outputs.*.notes' => 'nullable|string',
        ]);"""

content = content.replace(old_validation_update, new_validation_store, 1)

with open('c:\\laragon\\www\\USICS\\app\\Http\\Controllers\\Manufacturing\\BomController.php', 'w', encoding='utf-8') as f:
    f.write(content)
