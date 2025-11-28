<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsInventoryAlertsFilterPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->analyticsService = app(AnalyticsService::class);
    }

    /**
     * Feature: admin-analytics-dashboard, Property 14: Low stock filter accuracy
     * Validates: Requirements 9.2
     * 
     * @test
     */
    public function property_low_stock_filter_accuracy()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up before each iteration
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();

            // Create random number of products with different stock levels
            $numProducts = fake()->numberBetween(5, 20);
            $expectedLowStockCount = 0;
            $expectedOutOfStockCount = 0;
            
            for ($j = 0; $j < $numProducts; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);
                
                $reorderLevel = fake()->numberBetween(20, 200);
                
                // Randomly assign stock levels
                $stockType = fake()->randomElement(['low_stock', 'out_of_stock', 'healthy']);
                
                $quantity = match($stockType) {
                    'out_of_stock' => 0,
                    'low_stock' => fake()->numberBetween(1, $reorderLevel),
                    'healthy' => fake()->numberBetween($reorderLevel + 1, $reorderLevel * 3),
                };
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'reorder_level' => $reorderLevel,
                ]);

                // Track expected counts
                if ($quantity <= $reorderLevel) {
                    $expectedLowStockCount++;
                    if ($quantity <= 0) {
                        $expectedOutOfStockCount++;
                    }
                }
            }

            // Get inventory alerts from AnalyticsService
            $alerts = $this->analyticsService->getInventoryAlerts();

            // Property: For any inventory record in the low stock alerts, 
            // the quantity_available should be less than or equal to the reorder_level
            foreach ($alerts['items'] as $item) {
                $this->assertLessThanOrEqual(
                    $item['reorder_level'],
                    $item['quantity_available'],
                    "Iteration {$i}: All items in alerts should have quantity_available <= reorder_level. " .
                    "Found item with quantity {$item['quantity_available']} and reorder level {$item['reorder_level']}"
                );
            }

            // Property: The low_stock_count should match the number of items returned
            $this->assertEquals(
                $expectedLowStockCount,
                $alerts['low_stock_count'],
                "Iteration {$i}: low_stock_count should match expected count"
            );

            $this->assertEquals(
                $expectedLowStockCount,
                $alerts['items']->count(),
                "Iteration {$i}: Number of items should match low_stock_count"
            );

            // Property: The out_of_stock_count should match items with quantity_available <= 0
            $this->assertEquals(
                $expectedOutOfStockCount,
                $alerts['out_of_stock_count'],
                "Iteration {$i}: out_of_stock_count should match expected count"
            );

            $actualOutOfStockCount = $alerts['items']->where('quantity_available', '<=', 0)->count();
            $this->assertEquals(
                $expectedOutOfStockCount,
                $actualOutOfStockCount,
                "Iteration {$i}: out_of_stock_count should match items with quantity_available <= 0"
            );
        }
    }

    /**
     * Feature: admin-analytics-dashboard, Property 14: Low stock filter accuracy
     * Test that items are ordered by severity (lowest stock percentage first)
     * Validates: Requirements 9.4
     * 
     * @test
     */
    public function property_alerts_ordered_by_severity()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up before each iteration
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();

            // Create products with varying stock levels
            $numProducts = fake()->numberBetween(5, 15);
            
            for ($j = 0; $j < $numProducts; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);
                
                $reorderLevel = fake()->numberBetween(50, 200);
                $quantity = fake()->numberBetween(0, $reorderLevel);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantity,
                    'quantity_reserved' => 0,
                    'reorder_level' => $reorderLevel,
                ]);
            }

            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();

            // Property: Items should be ordered by stock_percentage ascending (lowest first)
            $previousPercentage = -1;
            foreach ($alerts['items'] as $item) {
                $this->assertGreaterThanOrEqual(
                    $previousPercentage,
                    $item['stock_percentage'],
                    "Iteration {$i}: Items should be ordered by stock_percentage ascending. " .
                    "Found {$item['stock_percentage']}% after {$previousPercentage}%"
                );
                $previousPercentage = $item['stock_percentage'];
            }
        }
    }

    /**
     * Feature: admin-analytics-dashboard, Property 14: Low stock filter accuracy
     * Test that stock percentage is calculated correctly
     * Validates: Requirements 9.2
     * 
     * @test
     */
    public function property_stock_percentage_calculation_accuracy()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up before each iteration
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();

            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);
            
            $reorderLevel = fake()->numberBetween(50, 200);
            $quantity = fake()->numberBetween(0, $reorderLevel);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
            ]);

            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();

            // Property: Stock percentage should be calculated as (quantity_available / reorder_level) * 100
            $item = $alerts['items']->first();
            $this->assertNotNull($item, "Iteration {$i}: Should have at least one alert");

            $expectedPercentage = $reorderLevel > 0
                ? round(($quantity / $reorderLevel) * 100, 2)
                : 0.0;

            $this->assertEquals(
                $expectedPercentage,
                $item['stock_percentage'],
                "Iteration {$i}: Stock percentage should be calculated correctly. " .
                "Expected {$expectedPercentage}% for {$quantity}/{$reorderLevel}, got {$item['stock_percentage']}%"
            );
        }
    }

    /**
     * Feature: admin-analytics-dashboard, Property 14: Low stock filter accuracy
     * Test edge case: zero reorder level
     * Validates: Requirements 9.2
     * 
     * @test
     */
    public function property_handles_zero_reorder_level()
    {
        // Run 50 iterations with random data
        for ($i = 0; $i < 50; $i++) {
            // Clean up before each iteration
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();

            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);
            
            // When reorder_level is 0, only quantity_available = 0 will trigger an alert
            // (since the condition is quantity_available <= reorder_level)
            $quantity = 0;
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => 0,
            ]);

            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();

            // Property: Items with reorder_level = 0 and quantity_available = 0 should be included
            // and stock_percentage should be 0.0 (not division by zero error)
            $item = $alerts['items']->first();
            $this->assertNotNull($item, "Iteration {$i}: Should have at least one alert when both reorder_level and quantity are 0");

            $this->assertEquals(
                0.0,
                $item['stock_percentage'],
                "Iteration {$i}: Stock percentage should be 0.0 when reorder_level is 0, not a division error"
            );
        }
    }

    /**
     * Feature: admin-analytics-dashboard, Property 14: Low stock filter accuracy
     * Test that required fields are present in alert items
     * Validates: Requirements 9.3
     * 
     * @test
     */
    public function property_alert_items_contain_required_fields()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up before each iteration
            Inventory::query()->delete();
            Product::where('category_id', $this->category->id)->delete();

            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);
            
            $reorderLevel = fake()->numberBetween(50, 200);
            $quantity = fake()->numberBetween(0, $reorderLevel);
            $location = fake()->randomElement(['main_warehouse', 'storage_room', 'retail_floor']);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
                'location' => $location,
            ]);

            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();

            // Property: Each alert item must contain required fields
            $item = $alerts['items']->first();
            $this->assertNotNull($item, "Iteration {$i}: Should have at least one alert");

            // Required fields from Requirements 9.3
            $this->assertArrayHasKey('product_name', $item, "Iteration {$i}: Must have product_name");
            $this->assertArrayHasKey('quantity_available', $item, "Iteration {$i}: Must have quantity_available");
            $this->assertArrayHasKey('reorder_level', $item, "Iteration {$i}: Must have reorder_level");
            $this->assertArrayHasKey('location', $item, "Iteration {$i}: Must have location");
            $this->assertArrayHasKey('stock_percentage', $item, "Iteration {$i}: Must have stock_percentage");

            // Verify field values
            $this->assertEquals($product->name, $item['product_name'], "Iteration {$i}: product_name should match");
            $this->assertEquals($quantity, $item['quantity_available'], "Iteration {$i}: quantity_available should match");
            $this->assertEquals($reorderLevel, $item['reorder_level'], "Iteration {$i}: reorder_level should match");
            $this->assertEquals($location, $item['location'], "Iteration {$i}: location should match");
        }
    }
}
