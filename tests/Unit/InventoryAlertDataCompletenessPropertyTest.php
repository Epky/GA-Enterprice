<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 10: Inventory alert data completeness
 * 
 * Property: For any low stock alert displayed, the alert should include product name, 
 * current quantity, and reorder level
 * 
 * Validates: Requirements 4.1
 */
class InventoryAlertDataCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property: For any low stock alert, the alert data must include product_name, 
     * quantity_available, and reorder_level
     * 
     * @test
     */
    public function inventory_alert_data_includes_required_fields()
    {
        // Run property test 100 times with different random data
        for ($i = 0; $i < 100; $i++) {
            // Clean up before each iteration to avoid constraint violations
            Inventory::query()->delete();
            ProductVariant::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            
            // Generate random inventory items with low stock
            $itemCount = rand(1, 10);
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = Product::factory()->create();
                
                // Create variant randomly (50% chance)
                $variant = rand(0, 1) ? ProductVariant::factory()->create(['product_id' => $product->id]) : null;
                
                // Generate random quantities where quantity_available <= reorder_level
                $reorderLevel = rand(10, 50);
                $quantityAvailable = rand(0, $reorderLevel); // Ensures low stock condition
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'quantity_available' => $quantityAvailable,
                    'reorder_level' => $reorderLevel,
                    'location' => fake()->randomElement(['warehouse', 'store_front', 'online']),
                ]);
            }
            
            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();
            
            // Property: Every alert item must contain product_name, quantity_available, and reorder_level
            $this->assertIsArray($alerts, "Iteration {$i}: getInventoryAlerts should return an array");
            $this->assertArrayHasKey('items', $alerts, "Iteration {$i}: Alerts should have 'items' key");
            
            $items = $alerts['items'];
            
            foreach ($items as $index => $item) {
                $this->assertIsArray($item, "Iteration {$i}, Item {$index}: Each alert item should be an array");
                
                // Check required fields exist
                $this->assertArrayHasKey('product_name', $item, 
                    "Iteration {$i}, Item {$index}: Alert must include product_name");
                $this->assertArrayHasKey('quantity_available', $item, 
                    "Iteration {$i}, Item {$index}: Alert must include quantity_available");
                $this->assertArrayHasKey('reorder_level', $item, 
                    "Iteration {$i}, Item {$index}: Alert must include reorder_level");
                
                // Check field types and values
                $this->assertIsString($item['product_name'], 
                    "Iteration {$i}, Item {$index}: product_name should be a string");
                $this->assertNotEmpty($item['product_name'], 
                    "Iteration {$i}, Item {$index}: product_name should not be empty");
                
                $this->assertIsNumeric($item['quantity_available'], 
                    "Iteration {$i}, Item {$index}: quantity_available should be numeric");
                $this->assertGreaterThanOrEqual(0, $item['quantity_available'], 
                    "Iteration {$i}, Item {$index}: quantity_available should be >= 0");
                
                $this->assertIsNumeric($item['reorder_level'], 
                    "Iteration {$i}, Item {$index}: reorder_level should be numeric");
                $this->assertGreaterThanOrEqual(0, $item['reorder_level'], 
                    "Iteration {$i}, Item {$index}: reorder_level should be >= 0");
            }
        }
    }
}
