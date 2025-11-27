<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 2: Recent movements limit
 * 
 * Validates: Requirements 1.3
 */
class DashboardRecentMovementsLimitPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2: Recent movements limit
     * 
     * For any collection of movements, when displayed on the dashboard,
     * at most 10 movements should be shown
     */
    public function test_dashboard_displays_at_most_10_movements(): void
    {
        // Run the property test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runPropertyTest();
        }
    }

    private function runPropertyTest(): void
    {
        // Use database transactions to isolate each iteration
        \DB::beginTransaction();
        
        try {
            // Arrange: Create a random number of business movements (between 0 and 50)
            $product = Product::factory()->create();
            $user = User::factory()->create();

        $businessTypes = InventoryMovement::BUSINESS_MOVEMENT_TYPES;
        $movementCount = rand(0, 50);
        
        for ($i = 0; $i < $movementCount; $i++) {
            // Only create business movements
            $movementType = $businessTypes[array_rand($businessTypes)];
            
            InventoryMovement::create([
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'quantity' => rand(-100, 100),
                'location_to' => 'main_warehouse',
                'notes' => 'Test movement ' . $i,
                'performed_by' => $user->id,
                'created_at' => now()->subHours(rand(1, 167)), // Within last 7 days (1-167 hours)
            ]);
        }

        // Act: Get movements through InventoryService as the dashboard does
        $inventoryService = new InventoryService();
        $recentMovements = $inventoryService->getInventoryMovements([
            'include_system_movements' => false,
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'group_related' => false
        ], 10);

        // Assert: At most 10 movements should be returned
        $actualCount = $recentMovements->count();
        $this->assertLessThanOrEqual(
            10,
            $actualCount,
            "Expected at most 10 movements in dashboard, but found {$actualCount}"
        );

        // Verify the property: regardless of how many movements exist, we never get more than 10
        $this->assertTrue(
            $actualCount <= 10,
            "Property violated: Dashboard should display at most 10 movements, but found {$actualCount}"
        );

        } finally {
            // Rollback transaction to clean up for next iteration
            \DB::rollBack();
        }
    }
}
