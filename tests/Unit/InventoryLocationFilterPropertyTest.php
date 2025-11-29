<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 13: Location filter affects inventory alerts
 * 
 * Property: For any location filter applied, only inventory alerts from that location should be displayed
 * 
 * Validates: Requirements 4.5
 */
class InventoryLocationFilterPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property Test: Location filter only returns alerts from specified location
     * 
     * For any location filter applied, the returned alerts should only contain items from that location
     * 
     * @test
     */
    public function property_location_filter_only_returns_alerts_from_specified_location()
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create products for different locations
            $product1 = Product::factory()->create();
            $product2 = Product::factory()->create();
            $product3 = Product::factory()->create();
            
            // Define test locations
            $locations = ['warehouse', 'store_front', 'online_storage'];
            $targetLocation = $locations[array_rand($locations)];
            
            // Create inventory items in different locations with low stock
            $targetInventory = Inventory::factory()->create([
                'product_id' => $product1->id,
                'location' => $targetLocation,
                'quantity_available' => rand(1, 5),
                'reorder_level' => rand(10, 20),
            ]);
            
            $otherInventory1 = Inventory::factory()->create([
                'product_id' => $product2->id,
                'location' => $locations[($i + 1) % count($locations)], // Different location
                'quantity_available' => rand(1, 5),
                'reorder_level' => rand(10, 20),
            ]);
            
            $otherInventory2 = Inventory::factory()->create([
                'product_id' => $product3->id,
                'location' => $locations[($i + 2) % count($locations)], // Different location
                'quantity_available' => rand(1, 5),
                'reorder_level' => rand(10, 20),
            ]);
            
            // Get alerts with location filter
            $filteredAlerts = $this->analyticsService->getInventoryAlerts($targetLocation);
            
            // Get alerts without filter for comparison
            $allAlerts = $this->analyticsService->getInventoryAlerts();
            
            // Assert that filtered alerts only contain items from target location
            foreach ($filteredAlerts['items'] as $alert) {
                $this->assertEquals($targetLocation, $alert['location'], 
                    "Alert should only be from target location '{$targetLocation}', got '{$alert['location']}'");
            }
            
            // Assert that the target inventory is included in filtered results
            $targetAlert = $filteredAlerts['items']->firstWhere('id', $targetInventory->id);
            $this->assertNotNull($targetAlert, "Target inventory should be included in filtered results");
            
            // Assert that other location inventories are NOT included in filtered results
            $otherAlert1 = $filteredAlerts['items']->firstWhere('id', $otherInventory1->id);
            $otherAlert2 = $filteredAlerts['items']->firstWhere('id', $otherInventory2->id);
            
            if ($otherInventory1->location !== $targetLocation) {
                $this->assertNull($otherAlert1, "Inventory from different location should not be included");
            }
            
            if ($otherInventory2->location !== $targetLocation) {
                $this->assertNull($otherAlert2, "Inventory from different location should not be included");
            }
            
            // Assert that filtered count is less than or equal to total count
            $this->assertLessThanOrEqual($allAlerts['low_stock_count'], $filteredAlerts['low_stock_count'],
                "Filtered count should be less than or equal to total count");
            
            // Clean up
            $targetInventory->delete();
            $otherInventory1->delete();
            $otherInventory2->delete();
            $product1->delete();
            $product2->delete();
            $product3->delete();
        }
    }


    /**
     * Property Test: Location filter with non-existent location returns empty results
     * 
     * @test
     */
    public function property_location_filter_with_non_existent_location_returns_empty_results()
    {
        // Create some inventory items
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'warehouse',
            'quantity_available' => 1,
            'reorder_level' => 10,
        ]);
        
        // Filter by non-existent location
        $alerts = $this->analyticsService->getInventoryAlerts('non_existent_location');
        
        // Assert empty results
        $this->assertEquals(0, $alerts['low_stock_count'], "Should return 0 alerts for non-existent location");
        $this->assertEquals(0, $alerts['out_of_stock_count'], "Should return 0 out of stock for non-existent location");
        $this->assertTrue($alerts['items']->isEmpty(), "Should return empty items collection for non-existent location");
        
        // Clean up
        $inventory->delete();
        $product->delete();
    }

    /**
     * Property Test: Location filter preserves alert severity and other properties
     * 
     * @test
     */
    public function property_location_filter_preserves_alert_properties()
    {
        $product = Product::factory()->create();
        $location = 'test_location';
        
        // Create inventory with specific properties
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => $location,
            'quantity_available' => 2,
            'reorder_level' => 10, // This should result in 'critical' severity (20%)
        ]);
        
        // Get alerts with location filter
        $alerts = $this->analyticsService->getInventoryAlerts($location);
        $alert = $alerts['items']->first();
        
        // Assert all required properties are present and correct
        $this->assertNotNull($alert, "Alert should exist");
        $this->assertEquals($inventory->id, $alert['id'], "Alert ID should match inventory ID");
        $this->assertEquals($location, $alert['location'], "Alert location should match filter");
        $this->assertEquals(2, $alert['quantity_available'], "Quantity should be preserved");
        $this->assertEquals(10, $alert['reorder_level'], "Reorder level should be preserved");
        $this->assertEquals(20.0, $alert['stock_percentage'], "Stock percentage should be calculated correctly");
        $this->assertEquals('critical', $alert['severity'], "Severity should be calculated correctly");
        $this->assertArrayHasKey('product_name', $alert, "Product name should be included");
        
        // Clean up
        $inventory->delete();
        $product->delete();
    }

    /**
     * Property Test: Recent inventory movements respect location filter
     * 
     * @test
     */
    public function property_recent_movements_respect_location_filter()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        $targetLocation = 'warehouse';
        $otherLocation = 'store_front';
        
        // Create movements in different locations
        $targetMovement = \App\Models\InventoryMovement::factory()->create([
            'product_id' => $product1->id,
            'movement_type' => 'sale',
            'quantity' => -5,
            'location_from' => $targetLocation,
            'notes' => 'Sale from warehouse',
        ]);
        
        $otherMovement = \App\Models\InventoryMovement::factory()->create([
            'product_id' => $product2->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
            'location_to' => $otherLocation,
            'notes' => 'Purchase to store',
        ]);
        
        // Get movements with location filter
        $filteredMovements = $this->analyticsService->getRecentInventoryMovements(20, $targetLocation);
        
        // Assert that filtered movements only contain items from target location
        foreach ($filteredMovements as $movement) {
            $hasTargetLocation = ($movement->location_from === $targetLocation) || 
                               ($movement->location_to === $targetLocation);
            $this->assertTrue($hasTargetLocation, 
                "Movement should involve target location '{$targetLocation}'");
        }
        
        // Assert target movement is included
        $foundTarget = $filteredMovements->firstWhere('id', $targetMovement->id);
        $this->assertNotNull($foundTarget, "Target movement should be included in filtered results");
        
        // Clean up
        $targetMovement->delete();
        $otherMovement->delete();
        $product1->delete();
        $product2->delete();
    }
}
