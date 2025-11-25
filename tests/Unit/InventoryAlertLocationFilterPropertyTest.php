<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Brand;
use App\Models\Category;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: inventory-low-stock-alerts, Property 7: Location filtering is accurate
 * 
 * Property: For any specific location filter, all returned alerts should be from that location only,
 * and no alerts from other locations should be included.
 * 
 * Validates: Requirements 2.4
 */
class InventoryAlertLocationFilterPropertyTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    /**
     * Property Test: Location filtering returns only alerts from the specified location
     * 
     * For any specific location filter, all returned alerts should be from that location only,
     * and no alerts from other locations should be included.
     */
    public function test_location_filtering_returns_only_alerts_from_specified_location(): void
    {
        // Run the property test 100 times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random test data
            $locations = $this->generateRandomLocations();
            $targetLocation = $locations[array_rand($locations)];
            
            // Create products with inventory at different locations and stock levels
            $this->createRandomInventoryData($locations);
            
            // Get alerts filtered by target location
            $alerts = $this->inventoryService->detectLowStockWithThresholds([
                'location' => $targetLocation
            ]);
            
            // Property: All alerts should be from the target location only
            $this->assertLocationFilteringIsAccurate($alerts, $targetLocation);
            
            // Clean up for next iteration
            Inventory::query()->delete();
            Product::query()->delete();
            Brand::query()->delete();
            Category::query()->delete();
        }
    }

    /**
     * Generate random location names for testing
     */
    private function generateRandomLocations(): array
    {
        $possibleLocations = [
            'main_warehouse',
            'secondary_warehouse',
            'store_front',
            'distribution_center',
            'retail_outlet',
            'storage_facility',
            'branch_office'
        ];
        
        // Randomly select 2-4 locations
        $numLocations = rand(2, 4);
        shuffle($possibleLocations);
        return array_slice($possibleLocations, 0, $numLocations);
    }

    /**
     * Create random inventory data across multiple locations
     */
    private function createRandomInventoryData(array $locations): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        
        // Create 5-15 products
        $numProducts = rand(5, 15);
        
        for ($i = 0; $i < $numProducts; $i++) {
            $product = Product::factory()->create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
            ]);
            
            // Create inventory records at different locations
            foreach ($locations as $location) {
                // Randomly decide if this product exists at this location (70% chance)
                if (rand(1, 100) <= 70) {
                    $reorderLevel = rand(20, 100);
                    
                    // Randomly assign stock levels to create different alert types
                    $stockLevel = $this->generateRandomStockLevel($reorderLevel);
                    
                    Inventory::factory()->create([
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'location' => $location,
                        'quantity_available' => $stockLevel,
                        'quantity_reserved' => 0,
                        'reorder_level' => $reorderLevel,
                        'reorder_quantity' => rand(50, 200),
                    ]);
                }
            }
        }
    }

    /**
     * Generate random stock level that creates alerts
     */
    private function generateRandomStockLevel(int $reorderLevel): int
    {
        $alertType = rand(1, 100);
        
        if ($alertType <= 30) {
            // Out of stock (30% chance)
            return 0;
        } elseif ($alertType <= 60) {
            // Critical stock: 1 to 25% of reorder level (30% chance)
            return rand(1, (int)($reorderLevel * 0.25));
        } elseif ($alertType <= 85) {
            // Low stock: 26% to 50% of reorder level (25% chance)
            return rand((int)($reorderLevel * 0.26), (int)($reorderLevel * 0.50));
        } else {
            // Healthy stock: above 50% (15% chance)
            return rand((int)($reorderLevel * 0.51), $reorderLevel * 2);
        }
    }

    /**
     * Assert that location filtering is accurate
     */
    private function assertLocationFilteringIsAccurate(array $alerts, string $targetLocation): void
    {
        // Check all alert categories
        $allAlerts = collect([
            ...$alerts['alerts']['critical_stock'],
            ...$alerts['alerts']['low_stock'],
            ...$alerts['alerts']['out_of_stock'],
        ]);
        
        foreach ($allAlerts as $alert) {
            $inventory = $alert['inventory'];
            
            // Property: Every alert must be from the target location
            $this->assertEquals(
                $targetLocation,
                $inventory->location,
                "Alert for product '{$inventory->display_name}' has location '{$inventory->location}' " .
                "but should only show alerts from location '{$targetLocation}'"
            );
        }
        
        // Additional check: Verify no alerts from other locations leaked through
        $alertLocations = $allAlerts->pluck('inventory.location')->unique();
        
        if ($alertLocations->isNotEmpty()) {
            $this->assertCount(
                1,
                $alertLocations,
                "Alerts contain multiple locations: " . $alertLocations->implode(', ') . 
                ". Should only contain: {$targetLocation}"
            );
            
            $this->assertEquals(
                $targetLocation,
                $alertLocations->first(),
                "The only location in alerts should be {$targetLocation}"
            );
        }
    }

    /**
     * Edge case: Test with null location (should return all locations)
     */
    public function test_null_location_returns_alerts_from_all_locations(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        
        // Create inventory at multiple locations with low stock
        $locations = ['main_warehouse', 'secondary_warehouse', 'store_front'];
        foreach ($locations as $location) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'location' => $location,
                'quantity_available' => 5,
                'reorder_level' => 50,
            ]);
        }
        
        // Get alerts without location filter
        $alerts = $this->inventoryService->detectLowStockWithThresholds([
            'location' => null
        ]);
        
        $allAlerts = collect([
            ...$alerts['alerts']['critical_stock'],
            ...$alerts['alerts']['low_stock'],
            ...$alerts['alerts']['out_of_stock'],
        ]);
        
        // Should have alerts from all locations
        $alertLocations = $allAlerts->pluck('inventory.location')->unique()->sort()->values();
        $expectedLocations = collect($locations)->sort()->values();
        
        $this->assertEquals(
            $expectedLocations,
            $alertLocations,
            "When location filter is null, should return alerts from all locations"
        );
    }

    /**
     * Edge case: Test with location that has no inventory
     */
    public function test_location_with_no_inventory_returns_empty_alerts(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        
        // Create inventory only at main_warehouse
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 5,
            'reorder_level' => 50,
        ]);
        
        // Get alerts for a different location
        $alerts = $this->inventoryService->detectLowStockWithThresholds([
            'location' => 'nonexistent_location'
        ]);
        
        // Should have no alerts
        $this->assertEquals(0, $alerts['summary']['total_alerts']);
        $this->assertEmpty($alerts['alerts']['critical_stock']);
        $this->assertEmpty($alerts['alerts']['low_stock']);
        $this->assertEmpty($alerts['alerts']['out_of_stock']);
    }
}
