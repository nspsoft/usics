<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductStock;

class MapSimulationSeeder extends Seeder
{
    public function run()
    {
        // 1. Get first warehouse
        $warehouse = Warehouse::first();
        if (!$warehouse) {
            $this->command->error("No warehouse found!");
            return;
        }

        $this->command->info("Simulating map for Warehouse: {$warehouse->name}");

        // Setup Grid 12x8
        $warehouse->update(['grid_cols' => 12, 'grid_rows' => 8]);

        // Get some products (if available)
        $products = Product::limit(10)->get();

        // 2. Map Layout Data
        $layoutData = [
            // Rack A
            ['code' => 'RK-A1', 'name' => 'Rack A Level 1', 'x' => 1, 'y' => 1, 'cap' => 1000, 'fill' => 950], // >90%
            ['code' => 'RK-A2', 'name' => 'Rack A Level 2', 'x' => 1, 'y' => 3, 'cap' => 1000, 'fill' => 750], // >70%
            ['code' => 'RK-A3', 'name' => 'Rack A Level 3', 'x' => 1, 'y' => 5, 'cap' => 1000, 'fill' => 500], // >40%
            ['code' => 'RK-A4', 'name' => 'Rack A Level 4', 'x' => 1, 'y' => 7, 'cap' => 1000, 'fill' => 150], // <40%
            
            // Rack B
            ['code' => 'RK-B1', 'name' => 'Rack B Level 1', 'x' => 4, 'y' => 1, 'cap' => 2000, 'fill' => 1950],
            ['code' => 'RK-B2', 'name' => 'Rack B Level 2', 'x' => 4, 'y' => 3, 'cap' => 2000, 'fill' => 1200],
            ['code' => 'RK-B3', 'name' => 'Rack B Level 3', 'x' => 4, 'y' => 5, 'cap' => 2000, 'fill' => 0],   // Empty
            ['code' => 'RK-B4', 'name' => 'Rack B Level 4', 'x' => 4, 'y' => 7, 'cap' => 2000, 'fill' => 100],

            // Rack C (Horizontal blocks)
            ['code' => 'FL-C1', 'name' => 'Floor C Block 1', 'x' => 7, 'y' => 1, 'w' => 2, 'h' => 2, 'cap' => 5000, 'fill' => 4800],
            ['code' => 'FL-C2', 'name' => 'Floor C Block 2', 'x' => 7, 'y' => 4, 'w' => 2, 'h' => 2, 'cap' => 5000, 'fill' => 2000],

            // Receiving / Quality Area
            ['code' => 'RCV-01', 'name' => 'Receiving Area', 'x' => 10, 'y' => 1, 'w' => 2, 'h' => 3, 'cap' => 10000, 'fill' => 8500],
            ['code' => 'QC-01',  'name' => 'Quality Control', 'x' => 10, 'y' => 5, 'w' => 2, 'h' => 3, 'cap' => 1000, 'fill' => 300],
        ];

        foreach ($layoutData as $slot) {
            // Create Location
            $loc = Location::updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $slot['code']],
                [
                    'name' => $slot['name'],
                    'type' => 'storage',
                    'level' => 1,
                    'path' => $slot['code'],
                    'pos_x' => $slot['x'],
                    'pos_y' => $slot['y'],
                    'width' => $slot['w'] ?? 1,
                    'height' => $slot['h'] ?? 1,
                    'capacity' => $slot['cap'],
                    'is_active' => true,
                ]
            );

            // Create Stock for this location if fill > 0 and products exist
            if ($slot['fill'] > 0 && $products->isNotEmpty()) {
                // Pick a random product
                $product = $products->random();
                
                // Clear existing stocks for this location to avoid duplication in simulation
                ProductStock::where('location_id', $loc->id)->delete();

                // Create stock
                ProductStock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $loc->id,
                    'qty_on_hand' => $slot['fill'],
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => $product->price ?? 10000,
                ]);
            }
        }

        $this->command->info("Map Simulation Seeder completed successfully!");
    }
}
