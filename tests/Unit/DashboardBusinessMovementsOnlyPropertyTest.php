<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Staff\DashboardController;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 1: Business movements only
 * 
 * Validates: Requirements 1.1
 */
class DashboardBusinessMovementsOnlyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: Business movements only
     * 
     * For any dashboard load, all movements displayed in the widget should have movement types
     * in the business movements set (purchase, sale, return, damage, adjustment, transfer),
     * never system movements (reservation, release)
     */
    public function test_dashboard_displays_only_business_movements(): void
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
                'created_at' => now()->subDays(rand(0, 6)), // Within last 7 days
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

        // Assert: All returned movements should be business movements
        foreach ($recentMovements->items() as $movement) {
            $this->assertTrue(
                in_array($movement->movement_type, $businessTypes),
                "Expected only business movements in dashboard, but found '{$movement->movement_type}' which is a system movement"
            );
        }

        } finally {
            // Rollback transaction to clean up for next iteration
            \DB::rollBack();
        }
    }
}
