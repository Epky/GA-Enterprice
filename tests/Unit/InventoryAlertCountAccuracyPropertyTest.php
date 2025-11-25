<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryAlertCountAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->inventoryService = app(InventoryService::class);
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 2: Alert counts match actual alerts
     * Validates: Requirements 3.1, 3.2, 3.3
     * 
     * @test
     */
    public function property_alert_counts_match_actual_alerts()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Create random number of products with different stock levels
            $numProducts = fake()->numberBetween(5, 20);
            $products = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);
                
                $reorderLevel = fake()->numberBetween(20, 200);
                
                // Randomly assign stock levels across different categories
                $stockType = fake()->randomElement(['critical', 'warning', 'out_of_stock', 'healthy']);
                
                $quantity = match($stockType) {
                    'out_of_stock' => 0,
                    'critical' => fake()->numberBetween(1, (int)($reorderLevel * 0.25)),
                    'warning' => fake()->numberBetween((int)($reorderLevel * 0.25) + 1, (int)($reorderLevel * 0.50)),
                    'healthy' => fake()->numberBetween((int)($reorderLevel * 0.50) + 1, $reorderLevel * 3),
                };
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'reorder_level' => $reorderLevel,
                ]);
                
                $products[] = $product;
            }

            // Get alerts using detectLowStockWithThresholds
            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: critical_count + warning_count + out_of_stock_count should equal total number of alert items
            $criticalCount = $alerts['summary']['critical_count'];
            $warningCount = $alerts['summary']['low_stock_count'];
            $outOfStockCount = $alerts['summary']['out_of_stock_count'];
            $totalAlerts = $alerts['summary']['total_alerts'];

            $calculatedTotal = $criticalCount + $warningCount + $outOfStockCount;

            $this->assertEquals(
                $calculatedTotal,
                $totalAlerts,
                "Iteration {$i}: Sum of individual counts ({$criticalCount} + {$warningCount} + {$outOfStockCount} = {$calculatedTotal}) should equal total_alerts ({$totalAlerts})"
            );

            // Property: Counts should match actual collection sizes
            $actualCriticalCount = $alerts['alerts']['critical_stock']->count();
            $actualWarningCount = $alerts['alerts']['low_stock']->count();
            $actualOutOfStockCount = $alerts['alerts']['out_of_stock']->count();

            $this->assertEquals(
                $criticalCount,
                $actualCriticalCount,
                "Iteration {$i}: critical_count ({$criticalCount}) should match actual critical alerts collection size ({$actualCriticalCount})"
            );

            $this->assertEquals(
                $warningCount,
                $actualWarningCount,
                "Iteration {$i}: low_stock_count ({$warningCount}) should match actual warning alerts collection size ({$actualWarningCount})"
            );

            $this->assertEquals(
                $outOfStockCount,
                $actualOutOfStockCount,
                "Iteration {$i}: out_of_stock_count ({$outOfStockCount}) should match actual out of stock alerts collection size ({$actualOutOfStockCount})"
            );

            // Clean up
            foreach ($products as $product) {
                $product->inventory()->delete();
                $product->delete();
            }
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 2: Alert counts match actual alerts
     * Test with location filter
     * Validates: Requirements 3.1, 3.2, 3.3
     * 
     * @test
     */
    public function property_alert_counts_match_with_location_filter()
    {
        // Run 50 iterations with random data
        for ($i = 0; $i < 50; $i++) {
            $locations = ['main_warehouse', 'secondary_warehouse', 'store_front'];
            $testLocation = fake()->randomElement($locations);
            
            // Create products in different locations
            $numProducts = fake()->numberBetween(5, 15);
            $products = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);
                
                $location = fake()->randomElement($locations);
                $reorderLevel = fake()->numberBetween(20, 200);
                
                // Randomly assign stock levels
                $stockType = fake()->randomElement(['critical', 'warning', 'out_of_stock', 'healthy']);
                
                $quantity = match($stockType) {
                    'out_of_stock' => 0,
                    'critical' => fake()->numberBetween(1, (int)($reorderLevel * 0.25)),
                    'warning' => fake()->numberBetween((int)($reorderLevel * 0.25) + 1, (int)($reorderLevel * 0.50)),
                    'healthy' => fake()->numberBetween((int)($reorderLevel * 0.50) + 1, $reorderLevel * 3),
                };
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'reorder_level' => $reorderLevel,
                    'location' => $location,
                ]);
                
                $products[] = $product;
            }

            // Get alerts with location filter
            $alerts = $this->inventoryService->detectLowStockWithThresholds(['location' => $testLocation]);

            // Property: Counts should match actual collection sizes for filtered location
            $criticalCount = $alerts['summary']['critical_count'];
            $warningCount = $alerts['summary']['low_stock_count'];
            $outOfStockCount = $alerts['summary']['out_of_stock_count'];
            $totalAlerts = $alerts['summary']['total_alerts'];

            $actualCriticalCount = $alerts['alerts']['critical_stock']->count();
            $actualWarningCount = $alerts['alerts']['low_stock']->count();
            $actualOutOfStockCount = $alerts['alerts']['out_of_stock']->count();

            $this->assertEquals(
                $criticalCount,
                $actualCriticalCount,
                "Iteration {$i}: critical_count for location {$testLocation} should match actual collection size"
            );

            $this->assertEquals(
                $warningCount,
                $actualWarningCount,
                "Iteration {$i}: low_stock_count for location {$testLocation} should match actual collection size"
            );

            $this->assertEquals(
                $outOfStockCount,
                $actualOutOfStockCount,
                "Iteration {$i}: out_of_stock_count for location {$testLocation} should match actual collection size"
            );

            $this->assertEquals(
                $criticalCount + $warningCount + $outOfStockCount,
                $totalAlerts,
                "Iteration {$i}: Sum of counts for location {$testLocation} should equal total_alerts"
            );

            // Property: All alerts should be from the filtered location
            $allAlerts = collect($alerts['alerts']['critical_stock'])
                ->merge($alerts['alerts']['low_stock'])
                ->merge($alerts['alerts']['out_of_stock']);

            foreach ($allAlerts as $alert) {
                $this->assertEquals(
                    $testLocation,
                    $alert['inventory']->location,
                    "Iteration {$i}: All alerts should be from location {$testLocation}"
                );
            }

            // Clean up
            foreach ($products as $product) {
                $product->inventory()->delete();
                $product->delete();
            }
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 2: Alert counts match actual alerts
     * Test getLowStockAlertDashboard method
     * Validates: Requirements 3.1, 3.2, 3.3
     * 
     * @test
     */
    public function property_dashboard_alert_counts_match_actual_alerts()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up all inventory and products before each iteration to avoid accumulation
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();
            
            // Create random number of products with different stock levels
            $numProducts = fake()->numberBetween(5, 20);
            $products = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);
                
                $reorderLevel = fake()->numberBetween(20, 200);
                
                // Randomly assign stock levels
                $stockType = fake()->randomElement(['critical', 'warning', 'out_of_stock', 'healthy']);
                
                $quantity = match($stockType) {
                    'out_of_stock' => 0,
                    'critical' => fake()->numberBetween(1, (int)($reorderLevel * 0.25)),
                    'warning' => fake()->numberBetween((int)($reorderLevel * 0.25) + 1, (int)($reorderLevel * 0.50)),
                    'healthy' => fake()->numberBetween((int)($reorderLevel * 0.50) + 1, $reorderLevel * 3),
                };
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'reorder_level' => $reorderLevel,
                ]);
                
                $products[] = $product;
            }

            // Get dashboard data
            $dashboard = $this->inventoryService->getLowStockAlertDashboard();

            // Property: alert_counts should sum to total_alerts
            $criticalCount = $dashboard['alert_counts']['critical'];
            $warningCount = $dashboard['alert_counts']['warning'];
            $errorCount = $dashboard['alert_counts']['error'];
            $totalAlerts = $dashboard['total_alerts'];

            $calculatedTotal = $criticalCount + $warningCount + $errorCount;

            $this->assertEquals(
                $calculatedTotal,
                $totalAlerts,
                "Iteration {$i}: Dashboard sum of counts ({$criticalCount} + {$warningCount} + {$errorCount} = {$calculatedTotal}) should equal total_alerts ({$totalAlerts})"
            );

            // Property: Dashboard counts should match the underlying getInventoryAlerts counts
            $alerts = $this->inventoryService->getInventoryAlerts();
            
            $actualCriticalCount = $alerts['critical_stock']->count();
            $actualWarningCount = $alerts['low_stock']->count();
            $actualErrorCount = $alerts['out_of_stock']->count();
            
            $this->assertEquals(
                $actualCriticalCount,
                $criticalCount,
                "Iteration {$i}: Dashboard critical count should match getInventoryAlerts critical count"
            );

            $this->assertEquals(
                $actualWarningCount,
                $warningCount,
                "Iteration {$i}: Dashboard warning count should match getInventoryAlerts low_stock count"
            );

            $this->assertEquals(
                $actualErrorCount,
                $errorCount,
                "Iteration {$i}: Dashboard error count should match getInventoryAlerts out_of_stock count"
            );
        }
    }
}
