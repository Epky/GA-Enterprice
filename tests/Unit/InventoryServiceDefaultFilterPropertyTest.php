<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: inventory-movements-display-improvement, Property 1: Default business-only filtering
 * 
 * Validates: Requirements 1.1
 */
class InventoryServiceDefaultFilterPropertyTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    /**
     * Property 1: Default business-only filtering
     * 
     * For any request to the movements page without the include_system_movements parameter,
     * all returned movements should have movement types in the business movements set
     * (purchase, sale, return, damage, adjustment, transfer)
     */
    public function test_default_filter_returns_only_business_movements(): void
    {
        // Run the property test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runPropertyTest();
        }
    }

    private function runPropertyTest(): void
    {
        // Arrange: Create a random mix of business and system movements
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $businessTypes = InventoryMovement::BUSINESS_MOVEMENT_TYPES;
        $systemTypes = InventoryMovement::SYSTEM_MOVEMENT_TYPES;
        $allTypes = array_merge($businessTypes, $systemTypes);

        // Generate random number of movements (between 5 and 20)
        $movementCount = rand(5, 20);
        
        for ($i = 0; $i < $movementCount; $i++) {
            // Randomly pick a movement type from all types
            $movementType = $allTypes[array_rand($allTypes)];
            
            InventoryMovement::create([
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'quantity' => rand(-100, 100),
                'location_to' => 'main_warehouse',
                'notes' => 'Test movement ' . $i,
                'performed_by' => $user->id,
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Act: Get movements without include_system_movements parameter (default behavior)
        // Disable grouping to get raw movement models
        $result = $this->inventoryService->getInventoryMovements(['group_related' => false], 100);

        // Assert: All returned movements should be business movements
        foreach ($result->items() as $movement) {
            $this->assertTrue(
                in_array($movement->movement_type, $businessTypes),
                "Expected only business movements, but found '{$movement->movement_type}' which is not in business types"
            );
        }

        // Clean up for next iteration
        InventoryMovement::query()->delete();
        Product::query()->delete();
        User::query()->delete();
    }
}
