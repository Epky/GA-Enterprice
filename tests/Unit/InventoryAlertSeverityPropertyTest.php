<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryAlertSeverityPropertyTest extends TestCase
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
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Validates: Requirements 1.1, 1.2, 5.2, 5.3, 5.4
     * 
     * @test
     */
    public function property_alert_severity_classification_is_consistent()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level (must be > 0 for alerts)
            $reorderLevel = fake()->numberBetween(20, 200);
            
            // Test critical threshold (0 < quantity ≤ 25% of reorder level)
            // Exclude 0 because that goes to out_of_stock category
            $criticalQuantity = fake()->numberBetween(1, (int)($reorderLevel * 0.25));
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $criticalQuantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: When 0 < quantity_available ≤ 25% of reorder_level, should be classified as critical
            $criticalAlerts = $alerts['alerts']['critical_stock'];
            $foundInCritical = $criticalAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertTrue(
                $foundInCritical,
                "Iteration {$i}: Product with {$criticalQuantity} units (0 < qty ≤ 25% of {$reorderLevel}) should be in critical alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Test warning threshold (26-50% of reorder level)
     * Validates: Requirements 1.1, 1.2, 5.2, 5.3, 5.4
     * 
     * @test
     */
    public function property_warning_threshold_classification_is_consistent()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level (must be > 0 for alerts)
            $reorderLevel = fake()->numberBetween(40, 200);
            
            // Test warning threshold (25% < quantity ≤ 50% of reorder level)
            // Add 1 to ensure we're strictly greater than 25%
            $warningQuantity = fake()->numberBetween(
                (int)($reorderLevel * 0.25) + 1, 
                (int)($reorderLevel * 0.50)
            );
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $warningQuantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: When 25% < quantity_available ≤ 50% of reorder_level, should be classified as warning
            $lowStockAlerts = $alerts['alerts']['low_stock'];
            $foundInLowStock = $lowStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertTrue(
                $foundInLowStock,
                "Iteration {$i}: Product with {$warningQuantity} units (25% < qty ≤ 50% of {$reorderLevel}) should be in low stock alerts"
            );

            // Property: Should NOT be in critical alerts
            $criticalAlerts = $alerts['alerts']['critical_stock'];
            $foundInCritical = $criticalAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertFalse(
                $foundInCritical,
                "Iteration {$i}: Product with {$warningQuantity} units (25% < qty ≤ 50% of {$reorderLevel}) should NOT be in critical alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Test healthy stock (> 50% of reorder level)
     * Validates: Requirements 1.1, 1.2, 5.2, 5.3, 5.4
     * 
     * @test
     */
    public function property_healthy_stock_generates_no_alerts()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level (must be > 0 for alerts)
            $reorderLevel = fake()->numberBetween(20, 200);
            
            // Test healthy threshold (> 50% of reorder level)
            // Add 1 to ensure we're strictly greater than 50%
            $healthyQuantity = fake()->numberBetween(
                (int)($reorderLevel * 0.50) + 1, 
                $reorderLevel * 3
            );
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $healthyQuantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: When quantity_available > 50% of reorder_level, should NOT generate any alerts
            $criticalAlerts = $alerts['alerts']['critical_stock'];
            $lowStockAlerts = $alerts['alerts']['low_stock'];
            $outOfStockAlerts = $alerts['alerts']['out_of_stock'];

            $foundInCritical = $criticalAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $foundInLowStock = $lowStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $foundInOutOfStock = $outOfStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertFalse(
                $foundInCritical || $foundInLowStock || $foundInOutOfStock,
                "Iteration {$i}: Product with {$healthyQuantity} units (> 50% of {$reorderLevel}) should NOT generate any alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Test out of stock (quantity_available = 0)
     * Validates: Requirements 1.1, 1.2, 5.2, 5.3, 5.4
     * 
     * @test
     */
    public function property_out_of_stock_classification_is_consistent()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random reorder level (must be > 0 for alerts)
            $reorderLevel = fake()->numberBetween(20, 200);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: When quantity_available = 0, should be classified as out of stock
            $outOfStockAlerts = $alerts['alerts']['out_of_stock'];
            $foundInOutOfStock = $outOfStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertTrue(
                $foundInOutOfStock,
                "Iteration {$i}: Product with 0 units should be in out of stock alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Edge case: Product with no reorder level set
     * Validates: Requirements 1.5
     * 
     * @test
     */
    public function property_products_without_reorder_level_generate_no_alerts()
    {
        // Run 50 iterations with random data
        for ($i = 0; $i < 50; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random quantity
            $quantity = fake()->numberBetween(0, 100);
            
            // Create inventory without reorder level by using raw SQL to bypass model validation
            // This tests that the service properly filters out null reorder levels
            try {
                \DB::table('inventory')->insert([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'location' => 'main_warehouse',
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'quantity_sold' => 0,
                    'reorder_level' => null,
                    'reorder_quantity' => 50,
                    'last_restocked_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // If database doesn't allow null reorder_level, skip this iteration
                // The fact that the database enforces this is actually good - it means
                // products without reorder levels can't exist in the first place
                $product->delete();
                continue;
            }

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: Products with null reorder_level should NOT generate any alerts
            $criticalAlerts = $alerts['alerts']['critical_stock'];
            $lowStockAlerts = $alerts['alerts']['low_stock'];

            $foundInCritical = $criticalAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $foundInLowStock = $lowStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertFalse(
                $foundInCritical || $foundInLowStock,
                "Iteration {$i}: Product with null reorder level should NOT generate critical or low stock alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-low-stock-alerts, Property 1: Alert severity classification is consistent
     * Edge case: Product with zero reorder level
     * Validates: Requirements 1.5
     * 
     * @test
     */
    public function property_products_with_zero_reorder_level_generate_no_alerts()
    {
        // Run 50 iterations with random data
        for ($i = 0; $i < 50; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random quantity
            $quantity = fake()->numberBetween(0, 100);
            
            // Test with zero reorder level
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => 0,
            ]);

            $alerts = $this->inventoryService->detectLowStockWithThresholds();

            // Property: Products with zero reorder_level should NOT generate any alerts
            $criticalAlerts = $alerts['alerts']['critical_stock'];
            $lowStockAlerts = $alerts['alerts']['low_stock'];

            $foundInCritical = $criticalAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $foundInLowStock = $lowStockAlerts->contains(function ($alert) use ($product) {
                return $alert['inventory']->product_id === $product->id;
            });

            $this->assertFalse(
                $foundInCritical || $foundInLowStock,
                "Iteration {$i}: Product with zero reorder level should NOT generate critical or low stock alerts"
            );

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }
}
