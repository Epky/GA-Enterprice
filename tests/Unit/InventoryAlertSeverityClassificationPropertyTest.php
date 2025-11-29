<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 11: Inventory alerts have severity classification
 * 
 * Property: For any inventory alert displayed, the alert should be categorized as critical, warning, or normal based on stock levels
 * 
 * Validates: Requirements 4.2
 */
class InventoryAlertSeverityClassificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property Test: All inventory alerts have severity classification
     * 
     * For any inventory alert, the severity should be one of: out_of_stock, critical, warning, or normal
     * 
     * @test
     */
    public function property_all_inventory_alerts_have_severity_classification()
    {
        // Generate random test data
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a product
            $product = Product::factory()->create();
            
            // Generate random stock levels
            $reorderLevel = rand(10, 100);
            $quantityAvailable = rand(0, $reorderLevel); // Ensure it's at or below reorder level
            
            // Create inventory at or below reorder level
            $inventory = Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantityAvailable,
                'reorder_level' => $reorderLevel,
            ]);
            
            // Get inventory alerts
            $alerts = $this->analyticsService->getInventoryAlerts();
            
            // Find the alert for this inventory
            $alert = $alerts['items']->firstWhere('id', $inventory->id);
            
            // Assert the alert exists
            $this->assertNotNull($alert, "Alert should exist for inventory ID {$inventory->id}");
            
            // Assert severity field exists
            $this->assertArrayHasKey('severity', $alert, "Alert should have a severity field");
            
            // Assert severity is one of the valid values
            $validSeverities = ['out_of_stock', 'critical', 'warning', 'normal'];
            $this->assertContains(
                $alert['severity'],
                $validSeverities,
                "Severity should be one of: " . implode(', ', $validSeverities) . ". Got: {$alert['severity']}"
            );
            
            // Verify severity matches stock level logic
            $stockPercentage = $reorderLevel > 0 
                ? ($quantityAvailable / $reorderLevel) * 100 
                : 0;
            
            if ($quantityAvailable <= 0) {
                $this->assertEquals('out_of_stock', $alert['severity'], 
                    "Severity should be 'out_of_stock' when quantity is 0 or less");
            } elseif ($stockPercentage <= 25) {
                $this->assertEquals('critical', $alert['severity'], 
                    "Severity should be 'critical' when stock percentage is <= 25%");
            } elseif ($stockPercentage <= 50) {
                $this->assertEquals('warning', $alert['severity'], 
                    "Severity should be 'warning' when stock percentage is <= 50%");
            } else {
                $this->assertEquals('normal', $alert['severity'], 
                    "Severity should be 'normal' when stock percentage is > 50%");
            }
            
            // Clean up for next iteration
            $inventory->delete();
            $product->delete();
        }
    }

    /**
     * Property Test: Out of stock items are classified as out_of_stock severity
     * 
     * @test
     */
    public function property_out_of_stock_items_have_out_of_stock_severity()
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $product = Product::factory()->create();
            $reorderLevel = rand(10, 100);
            
            // Create inventory with 0 or negative quantity
            $inventory = Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'reorder_level' => $reorderLevel,
            ]);
            
            $alerts = $this->analyticsService->getInventoryAlerts();
            $alert = $alerts['items']->firstWhere('id', $inventory->id);
            
            $this->assertNotNull($alert);
            $this->assertEquals('out_of_stock', $alert['severity'], 
                "Items with 0 quantity should have 'out_of_stock' severity");
            
            $inventory->delete();
            $product->delete();
        }
    }

    /**
     * Property Test: Critical stock items (<=25% of reorder level) are classified as critical
     * 
     * @test
     */
    public function property_critical_stock_items_have_critical_severity()
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $product = Product::factory()->create();
            $reorderLevel = rand(20, 100);
            
            // Create inventory at 1-25% of reorder level
            $quantityAvailable = rand(1, (int)($reorderLevel * 0.25));
            
            $inventory = Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantityAvailable,
                'reorder_level' => $reorderLevel,
            ]);
            
            $alerts = $this->analyticsService->getInventoryAlerts();
            $alert = $alerts['items']->firstWhere('id', $inventory->id);
            
            $this->assertNotNull($alert);
            $this->assertEquals('critical', $alert['severity'], 
                "Items at or below 25% of reorder level should have 'critical' severity");
            
            $inventory->delete();
            $product->delete();
        }
    }

    /**
     * Property Test: Warning stock items (26-50% of reorder level) are classified as warning
     * 
     * @test
     */
    public function property_warning_stock_items_have_warning_severity()
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $product = Product::factory()->create();
            $reorderLevel = rand(40, 100); // Use higher reorder level to avoid rounding issues
            
            // Create inventory at 26-50% of reorder level
            // Ensure we're above 25% threshold
            $minQuantity = (int)ceil($reorderLevel * 0.26);
            $maxQuantity = (int)floor($reorderLevel * 0.50);
            
            // Skip if range is invalid
            if ($minQuantity > $maxQuantity) {
                $product->delete();
                continue;
            }
            
            $quantityAvailable = rand($minQuantity, $maxQuantity);
            
            $inventory = Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantityAvailable,
                'reorder_level' => $reorderLevel,
            ]);
            
            $alerts = $this->analyticsService->getInventoryAlerts();
            $alert = $alerts['items']->firstWhere('id', $inventory->id);
            
            $this->assertNotNull($alert);
            
            // Calculate actual percentage to verify
            $actualPercentage = ($quantityAvailable / $reorderLevel) * 100;
            
            $this->assertEquals('warning', $alert['severity'], 
                "Items at {$actualPercentage}% ({$quantityAvailable}/{$reorderLevel}) should have 'warning' severity");
            
            $inventory->delete();
            $product->delete();
        }
    }
}
