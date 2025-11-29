<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 12: Inventory movements include transaction references
 * 
 * Property: For any inventory movement displayed, the movement should include a transaction reference when applicable
 * 
 * Validates: Requirements 4.4
 */
class InventoryMovementTransactionReferenceDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property Test: All inventory movements with transaction references display them
     * 
     * For any inventory movement that has a transaction reference in notes, 
     * the transaction_reference accessor should extract and return it
     * 
     * @test
     */
    public function property_movements_with_transaction_references_display_them()
    {
        $iterations = 50;
        
        // Create a single product to reuse across iterations to avoid factory conflicts
        $product = Product::factory()->create();
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate a random walk-in transaction reference
            $transactionId = 'WI-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $notes = "Walk-in transaction completed: {$transactionId}";
            
            // Create movement with transaction reference in notes (use 'sale' which is a business movement)
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => 'sale', // Use business movement type
                'quantity' => -rand(1, 10),
                'notes' => $notes,
            ]);
            
            // Get recent movements
            $movements = $this->analyticsService->getRecentInventoryMovements(100);
            $foundMovement = $movements->firstWhere('id', $movement->id);
            
            $this->assertNotNull($foundMovement, "Movement should be found in recent movements");
            
            // Check that transaction_reference accessor works
            $transactionRef = $foundMovement->transaction_reference;
            
            $this->assertNotNull($transactionRef, "Transaction reference should not be null");
            $this->assertIsArray($transactionRef, "Transaction reference should be an array");
            $this->assertArrayHasKey('type', $transactionRef, "Transaction reference should have 'type' key");
            $this->assertArrayHasKey('id', $transactionRef, "Transaction reference should have 'id' key");
            $this->assertEquals('walk-in', $transactionRef['type'], "Transaction type should be 'walk-in'");
            $this->assertEquals($transactionId, $transactionRef['id'], "Transaction ID should match");
            
            // Clean up
            $movement->delete();
        }
        
        // Clean up product after all iterations
        $product->delete();
    }

    /**
     * Property Test: Movements without transaction references return null
     * 
     * @test
     */
    public function property_movements_without_transaction_references_return_null()
    {
        $iterations = 50;
        
        // Create a single product to reuse across iterations to avoid factory conflicts
        $product = Product::factory()->create();
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create movement without transaction reference
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => 'purchase',
                'quantity' => rand(1, 10),
                'notes' => 'Regular purchase without transaction reference',
            ]);
            
            // Get recent movements
            $movements = $this->analyticsService->getRecentInventoryMovements(100);
            $foundMovement = $movements->firstWhere('id', $movement->id);
            
            $this->assertNotNull($foundMovement);
            
            // Check that transaction_reference is null
            $this->assertNull($foundMovement->transaction_reference, 
                "Transaction reference should be null for movements without references");
            
            // Clean up
            $movement->delete();
        }
        
        // Clean up product after all iterations
        $product->delete();
    }

    /**
     * Property Test: Transaction references are displayed in the view
     * 
     * @test
     */
    public function property_transaction_references_are_accessible_in_movements_list()
    {
        $product = Product::factory()->create();
        
        // Create movements with and without transaction references
        $transactionId = 'WI-' . date('Ymd') . '-0001';
        
        $movementWithRef = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -5,
            'notes' => "Walk-in transaction completed: {$transactionId}",
        ]);
        
        $movementWithoutRef = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
            'notes' => 'Regular purchase',
        ]);
        
        // Get recent movements
        $movements = $this->analyticsService->getRecentInventoryMovements(100);
        
        // Find our movements
        $foundWithRef = $movements->firstWhere('id', $movementWithRef->id);
        $foundWithoutRef = $movements->firstWhere('id', $movementWithoutRef->id);
        
        $this->assertNotNull($foundWithRef);
        $this->assertNotNull($foundWithoutRef);
        
        // Verify transaction reference is present for one and absent for the other
        $this->assertNotNull($foundWithRef->transaction_reference);
        $this->assertEquals($transactionId, $foundWithRef->transaction_reference['id']);
        
        $this->assertNull($foundWithoutRef->transaction_reference);
        
        // Clean up
        $movementWithRef->delete();
        $movementWithoutRef->delete();
        $product->delete();
    }
}
