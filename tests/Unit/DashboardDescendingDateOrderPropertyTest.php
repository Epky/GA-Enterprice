<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 3: Descending date order
 * 
 * Validates: Requirements 1.5
 */
class DashboardDescendingDateOrderPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 3: Descending date order
     * 
     * For any collection of movements displayed on the dashboard, each movement should have
     * a creation date greater than or equal to the next movement in the list
     */
    public function test_dashboard_movements_are_ordered_by_date_descending(): void
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
            // Arrange: Create random number of business movements with various timestamps
            $product = Product::factory()->create();
            $user = User::factory()->create();

        $businessTypes = InventoryMovement::BUSINESS_MOVEMENT_TYPES;
        $movementCount = rand(5, 20);
        
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

        // Assert: Movements should be ordered by created_at descending
        $movements = $recentMovements->items();
        
        for ($i = 0; $i < count($movements) - 1; $i++) {
            $currentMovement = $movements[$i];
            $nextMovement = $movements[$i + 1];
            
            $this->assertGreaterThanOrEqual(
                $nextMovement->created_at->timestamp,
                $currentMovement->created_at->timestamp,
                "Property violated: Movement at index {$i} (created at {$currentMovement->created_at}) " .
                "should be more recent than or equal to movement at index " . ($i + 1) . 
                " (created at {$nextMovement->created_at})"
            );
        }

        } finally {
            // Rollback transaction to clean up for next iteration
            \DB::rollBack();
        }
    }
}
