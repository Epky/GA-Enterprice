<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Feature: inventory-movements-display-improvement, Property 7: Multiple filter combination
 * 
 * Validates: Requirements 4.4
 */
class InventoryServiceMultipleFilterCombinationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    /**
     * Property 7: Multiple filter combination
     * 
     * For any combination of filters (movement_type, location, date_range),
     * the query should return only movements that match all specified criteria
     */
    public function test_multiple_filters_combine_with_and_logic(): void
    {
        // Run the property test multiple times with different random data
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $this->runPropertyTest();
        }
    }

    private function runPropertyTest(): void
    {
        // Arrange: Create diverse movements with different attributes
        // Use existing products if available, otherwise create new ones
        $existingProducts = Product::limit(3)->get();
        if ($existingProducts->count() >= 3) {
            $products = $existingProducts;
        } else {
            $products = Product::factory()->count(3)->create();
        }
        
        $existingUsers = User::limit(2)->get();
        if ($existingUsers->count() >= 2) {
            $users = $existingUsers;
        } else {
            $users = User::factory()->count(2)->create();
        }

        $locations = ['main_warehouse', 'store_front', 'storage_room'];
        $movementTypes = InventoryMovement::BUSINESS_MOVEMENT_TYPES;

        // Create movements with various combinations of attributes
        $movements = [];
        for ($i = 0; $i < 30; $i++) {
            $movement = InventoryMovement::create([
                'product_id' => $products->random()->id,
                'movement_type' => $movementTypes[array_rand($movementTypes)],
                'quantity' => rand(-100, 100),
                'location_to' => $locations[array_rand($locations)],
                'notes' => 'Test movement ' . $i,
                'performed_by' => $users->random()->id,
                'created_at' => Carbon::now()->subDays(rand(0, 60)),
            ]);
            $movements[] = $movement;
        }

        // Act: Apply random combination of filters
        $filters = [];
        
        // Randomly decide which filters to apply
        $applyProductFilter = rand(0, 1) === 1;
        $applyMovementTypeFilter = rand(0, 1) === 1;
        $applyLocationFilter = rand(0, 1) === 1;
        $applyDateRangeFilter = rand(0, 1) === 1;
        $applyUserFilter = rand(0, 1) === 1;

        if ($applyProductFilter) {
            $filters['product_id'] = $products->random()->id;
        }

        if ($applyMovementTypeFilter) {
            $filters['movement_type'] = $movementTypes[array_rand($movementTypes)];
        }

        if ($applyLocationFilter) {
            $filters['location'] = $locations[array_rand($locations)];
        }

        if ($applyDateRangeFilter) {
            $filters['start_date'] = Carbon::now()->subDays(30)->startOfDay();
            $filters['end_date'] = Carbon::now()->endOfDay();
        }

        if ($applyUserFilter) {
            $filters['performed_by'] = $users->random()->id;
        }

        // Disable grouping to get raw movement models
        $filters['group_related'] = false;
        $result = $this->inventoryService->getInventoryMovements($filters, 100);

        // Assert: All returned movements should match ALL applied filters
        foreach ($result->items() as $movement) {
            if ($applyProductFilter) {
                $this->assertEquals(
                    $filters['product_id'],
                    $movement->product_id,
                    "Movement should match product_id filter"
                );
            }

            if ($applyMovementTypeFilter) {
                $this->assertEquals(
                    $filters['movement_type'],
                    $movement->movement_type,
                    "Movement should match movement_type filter"
                );
            }

            if ($applyLocationFilter) {
                $locationMatches = $movement->location_to === $filters['location'] ||
                                   $movement->location_from === $filters['location'];
                $this->assertTrue(
                    $locationMatches,
                    "Movement should match location filter (either location_to or location_from)"
                );
            }

            if ($applyDateRangeFilter) {
                $this->assertTrue(
                    $movement->created_at >= $filters['start_date'] &&
                    $movement->created_at <= $filters['end_date'],
                    "Movement should be within date range filter"
                );
            }

            if ($applyUserFilter) {
                $this->assertEquals(
                    $filters['performed_by'],
                    $movement->performed_by,
                    "Movement should match performed_by filter"
                );
            }

            // Also verify it's a business movement (default filter)
            $this->assertTrue(
                in_array($movement->movement_type, InventoryMovement::BUSINESS_MOVEMENT_TYPES),
                "Movement should be a business movement (default filter)"
            );
        }

        // Clean up for next iteration (only movements, keep products and users for reuse)
        InventoryMovement::query()->delete();
    }
}
