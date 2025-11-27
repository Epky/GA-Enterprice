<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 9: Consistent badge colors
 * 
 * Property: For any movement type, the badge color class used in the dashboard widget
 * should match the badge color class used in the full movements page for the same type
 * 
 * Validates: Requirements 3.2
 */
class DashboardMovementBadgeColorPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Property: Badge colors are consistent across all movement types
     * 
     * For any movement type, getTypeBadgeColor() should return the same color
     * regardless of where it's called (dashboard or full movements page).
     * This ensures visual consistency across the application.
     */
    public function test_badge_colors_are_consistent_for_all_movement_types()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // All business movement types
        $movementTypes = [
            'purchase',
            'sale',
            'return',
            'damage',
            'adjustment',
            'transfer',
        ];

        foreach ($movementTypes as $type) {
            // Create multiple movements of the same type
            $movements = [];
            for ($i = 0; $i < 10; $i++) {
                $movements[] = InventoryMovement::factory()->create([
                    'product_id' => $product->id,
                    'movement_type' => $type,
                    'quantity' => rand(-50, 50),
                    'performed_by' => $user->id,
                ]);
            }

            // Property: All movements of the same type should have the same badge color
            $firstColor = $movements[0]->getTypeBadgeColor();
            
            foreach ($movements as $movement) {
                $this->assertEquals(
                    $firstColor,
                    $movement->getTypeBadgeColor(),
                    "Badge color for movement type '{$type}' should be consistent across all instances"
                );
            }
        }
    }

    /**
     * @test
     * Property: Badge colors are deterministic
     * 
     * For any movement type, calling getTypeBadgeColor() multiple times
     * should always return the same result.
     */
    public function test_badge_colors_are_deterministic()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $movementTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer'];

        foreach ($movementTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => $type,
                'quantity' => rand(-50, 50),
                'performed_by' => $user->id,
            ]);

            // Call the method multiple times
            $color1 = $movement->getTypeBadgeColor();
            $color2 = $movement->getTypeBadgeColor();
            $color3 = $movement->getTypeBadgeColor();

            // Property: Should always return the same value
            $this->assertEquals($color1, $color2);
            $this->assertEquals($color2, $color3);
            $this->assertEquals($color1, $color3);
        }
    }

    /**
     * @test
     * Property: All movement types have defined badge colors
     * 
     * For any valid movement type, getTypeBadgeColor() should return
     * a valid Tailwind CSS background color class.
     */
    public function test_all_movement_types_have_valid_badge_colors()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $movementTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer'];
        $validColorClasses = [
            'bg-blue-100',
            'bg-green-100',
            'bg-yellow-100',
            'bg-red-100',
            'bg-purple-100',
            'bg-indigo-100',
            'bg-orange-100',
            'bg-gray-100',
        ];

        foreach ($movementTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => $type,
                'quantity' => rand(-50, 50),
                'performed_by' => $user->id,
            ]);

            $badgeColor = $movement->getTypeBadgeColor();

            // Property: Badge color should be a valid Tailwind CSS class
            $this->assertContains(
                $badgeColor,
                $validColorClasses,
                "Badge color '{$badgeColor}' for movement type '{$type}' should be a valid Tailwind CSS class"
            );
        }
    }
}
