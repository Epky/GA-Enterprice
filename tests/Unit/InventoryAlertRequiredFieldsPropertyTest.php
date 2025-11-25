<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryAlertRequiredFieldsPropertyTest extends TestCase
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
     * Feature: inventory-low-stock-alerts, Property 6: Alert data contains required fields
     * Validates: Requirements 1.4, 2.3, 4.4
     * 
     * @test
     */
    public function property_alert_data_contains_required_fields()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level and quantity to trigger an alert
            $reorderLevel = fake()->numberBetween(20, 200);
            $quantity = fake()->numberBetween(0, (int)($reorderLevel * 0.50));
            $location = fake()->randomElement(['main_warehouse', 'storage_room', 'retail_floor']);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
                'location' => $location,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Get all alerts (critical, low_stock, or out_of_stock)
            $allAlerts = collect()
                ->merge($alerts['alerts']['critical_stock'])
                ->merge($alerts['alerts']['low_stock'])
                ->merge($alerts['alerts']['out_of_stock']);

            // Find the alert for this product
            $alert = $allAlerts->first(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            // Property: Alert must exist for products below threshold
            $this->assertNotNull(
                $alert,
                "Iteration {$i}: Alert should exist for product with {$quantity} units (threshold: {$reorderLevel})"
            );

            // Property: Alert must contain inventory object
            $this->assertArrayHasKey(
                'inventory',
                $alert,
                "Iteration {$i}: Alert must contain 'inventory' field"
            );

            $inventory = $alert['inventory'];

            // Property: Alert must contain product name (via inventory->display_name)
            $this->assertNotNull(
                $inventory->display_name,
                "Iteration {$i}: Alert must contain product name"
            );
            $this->assertIsString(
                $inventory->display_name,
                "Iteration {$i}: Product name must be a string"
            );

            // Property: Alert must contain current stock (quantity_available)
            $this->assertNotNull(
                $inventory->quantity_available,
                "Iteration {$i}: Alert must contain current stock (quantity_available)"
            );
            $this->assertIsInt(
                $inventory->quantity_available,
                "Iteration {$i}: Current stock must be an integer"
            );
            $this->assertEquals(
                $quantity,
                $inventory->quantity_available,
                "Iteration {$i}: Current stock should match the created quantity"
            );

            // Property: Alert must contain reorder level
            $this->assertNotNull(
                $inventory->reorder_level,
                "Iteration {$i}: Alert must contain reorder level"
            );
            $this->assertIsInt(
                $inventory->reorder_level,
                "Iteration {$i}: Reorder level must be an integer"
            );
            $this->assertEquals(
                $reorderLevel,
                $inventory->reorder_level,
                "Iteration {$i}: Reorder level should match the created value"
            );

            // Property: Alert must contain location
            $this->assertNotNull(
                $inventory->location,
                "Iteration {$i}: Alert must contain location"
            );
            $this->assertIsString(
                $inventory->location,
                "Iteration {$i}: Location must be a string"
            );
            $this->assertEquals(
                $location,
                $inventory->location,
                "Iteration {$i}: Location should match the created value"
            );

            // Property: Alert must contain severity level
            $this->assertArrayHasKey(
                'severity',
                $alert,
                "Iteration {$i}: Alert must contain 'severity' field"
            );
            $this->assertContains(
                $alert['severity'],
                ['critical', 'warning', 'error'],
                "Iteration {$i}: Severity must be one of: critical, warning, error"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 6: Alert data contains required fields
     * Test that stock percentage can be calculated from alert data
     * Validates: Requirements 1.4, 2.3, 4.4
     * 
     * @test
     */
    public function property_alert_data_allows_stock_percentage_calculation()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level and quantity to trigger an alert
            $reorderLevel = fake()->numberBetween(20, 200);
            $quantity = fake()->numberBetween(1, (int)($reorderLevel * 0.50));
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Get all alerts
            $allAlerts = collect()
                ->merge($alerts['alerts']['critical_stock'])
                ->merge($alerts['alerts']['low_stock'])
                ->merge($alerts['alerts']['out_of_stock']);

            // Find the alert for this product
            $alert = $allAlerts->first(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertNotNull($alert, "Iteration {$i}: Alert should exist");

            $inventory = $alert['inventory'];

            // Property: Stock percentage can be calculated from available data
            $expectedPercentage = ($inventory->quantity_available / $inventory->reorder_level) * 100;
            $calculatedPercentage = ($quantity / $reorderLevel) * 100;

            $this->assertEquals(
                $calculatedPercentage,
                $expectedPercentage,
                "Iteration {$i}: Stock percentage should be calculable from alert data",
                0.01 // Allow small floating point differences
            );

            // Property: Calculated percentage should match severity classification
            if ($alert['severity'] === 'critical') {
                $this->assertLessThanOrEqual(
                    25.0,
                    $expectedPercentage,
                    "Iteration {$i}: Critical alerts should have ≤ 25% stock"
                );
            } elseif ($alert['severity'] === 'warning') {
                $this->assertGreaterThan(
                    25.0,
                    $expectedPercentage,
                    "Iteration {$i}: Warning alerts should have > 25% stock"
                );
                $this->assertLessThanOrEqual(
                    50.0,
                    $expectedPercentage,
                    "Iteration {$i}: Warning alerts should have ≤ 50% stock"
                );
            }

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }
}
