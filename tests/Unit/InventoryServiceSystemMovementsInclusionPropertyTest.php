<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: inventory-movements-display-improvement, Property 2: System movements inclusion when toggled
 * 
 * Validates: Requirements 1.5
 */
class InventoryServiceSystemMovementsInclusionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    /**
     * Property 2: System movements inclusion when toggled
     * 
     * For any request with include_system_movements=true, the returned movements
     * should include both business and system movement types (reservation, release)
     */
    public function test_include_system_movements_returns_all_movement_types(): void
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

        // Track what types we actually created
        $createdBusinessTypes = [];
        $createdSystemTypes = [];

        // Generate random number of movements (between 10 and 30)
        $movementCount = rand(10, 30);
        
        for ($i = 0; $i < $movementCount; $i++) {
            // Randomly pick a movement type from all types
            $movementType = $allTypes[array_rand($allTypes)];
            
            if (in_array($movementType, $businessTypes)) {
                $createdBusinessTypes[] = $movementType;
            } else {
                $createdSystemTypes[] = $movementType;
            }
            
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

        // Act: Get movements with include_system_movements=true
        // Disable grouping to get raw movement models
        $result = $this->inventoryService->getInventoryMovements([
            'include_system_movements' => true,
            'group_related' => false
        ], 100);

        // Assert: Result should include both business and system movements
        $returnedTypes = $result->pluck('movement_type')->unique()->toArray();
        
        // All created business types should be in the result
        foreach (array_unique($createdBusinessTypes) as $businessType) {
            $this->assertContains(
                $businessType,
                $returnedTypes,
                "Expected business movement type '{$businessType}' to be in results when include_system_movements=true"
            );
        }

        // All created system types should be in the result
        foreach (array_unique($createdSystemTypes) as $systemType) {
            $this->assertContains(
                $systemType,
                $returnedTypes,
                "Expected system movement type '{$systemType}' to be in results when include_system_movements=true"
            );
        }

        // Verify the total count matches what we created
        $this->assertEquals(
            $movementCount,
            $result->total(),
            "Expected all {$movementCount} movements to be returned when include_system_movements=true"
        );

        // Clean up for next iteration
        InventoryMovement::query()->delete();
        Product::query()->delete();
        User::query()->delete();
    }
}
