<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 8: Quantity color coding
 * 
 * Property: For any movement displayed, if the quantity is positive it should use green color class,
 * if negative it should use red color class
 * 
 * Validates: Requirements 3.1
 */
class DashboardMovementQuantityColorPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Property: Quantity color coding consistency
     * 
     * For any movement with a positive quantity, getQuantityColorClass() should return green.
     * For any movement with a negative quantity, getQuantityColorClass() should return red.
     */
    public function test_quantity_color_coding_property()
    {
        // Create test data
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Test with 100 random quantities
        for ($i = 0; $i < 100; $i++) {
            // Generate random quantity between -100 and 100 (excluding 0)
            $quantity = rand(-100, 100);
            if ($quantity === 0) {
                $quantity = 1; // Avoid zero
            }

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
            ]);

            $colorClass = $movement->getQuantityColorClass();

            // Property: positive quantities should be green, negative should be red
            if ($quantity > 0) {
                $this->assertEquals(
                    'text-green-600',
                    $colorClass,
                    "Expected green color class for positive quantity {$quantity}, got {$colorClass}"
                );
            } else {
                $this->assertEquals(
                    'text-red-600',
                    $colorClass,
                    "Expected red color class for negative quantity {$quantity}, got {$colorClass}"
                );
            }
        }
    }

    /**
     * @test
     * Edge case: Zero quantity
     */
    public function test_zero_quantity_color()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $movement = InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 0,
            'movement_type' => 'adjustment',
            'performed_by' => $user->id,
        ]);

        $colorClass = $movement->getQuantityColorClass();

        // Zero should be gray
        $this->assertEquals('text-gray-600', $colorClass);
    }
}
