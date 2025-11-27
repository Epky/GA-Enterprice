<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementTypeBadgeColorPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);
        $this->user = User::factory()->create(['role' => 'staff']);
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 9: Consistent type badge colors
     * Validates: Requirements 5.2
     * 
     * @test
     */
    public function property_same_movement_type_always_gets_same_badge_color()
    {
        $movementTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer', 'reservation', 'release'];

        foreach ($movementTypes as $type) {
            $colors = [];

            // Create 20 movements of the same type and collect their badge colors
            for ($i = 0; $i < 20; $i++) {
                $movement = InventoryMovement::factory()->create([
                    'product_id' => $this->product->id,
                    'movement_type' => $type,
                    'quantity' => fake()->numberBetween(-100, 100),
                    'performed_by' => $this->user->id,
                ]);

                $badgeColor = $movement->getTypeBadgeColor();
                $colors[] = $badgeColor;

                // Clean up
                $movement->delete();
            }

            // Property: All movements of the same type should have the same badge color
            $uniqueColors = array_unique($colors);
            $this->assertCount(
                1,
                $uniqueColors,
                "Movement type '{$type}' should always map to the same badge color. Found colors: " . implode(', ', $uniqueColors)
            );
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 9: Consistent type badge colors
     * Validates: Requirements 5.2
     * 
     * @test
     */
    public function property_badge_color_is_deterministic_for_movement_type()
    {
        // Run 50 iterations to ensure consistency
        for ($i = 0; $i < 50; $i++) {
            $type = fake()->randomElement(['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer', 'reservation', 'release']);

            // Create two movements of the same type
            $movement1 = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $movement2 = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            // Property: Both movements should have the same badge color
            $color1 = $movement1->getTypeBadgeColor();
            $color2 = $movement2->getTypeBadgeColor();

            $this->assertEquals(
                $color1,
                $color2,
                "Iteration {$i}: Movement type '{$type}' should have consistent badge color. Got '{$color1}' and '{$color2}'"
            );

            // Clean up
            $movement1->delete();
            $movement2->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 9: Consistent type badge colors
     * Validates: Requirements 5.2
     * 
     * @test
     */
    public function property_all_movement_types_have_defined_badge_colors()
    {
        $movementTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer', 'reservation', 'release'];

        foreach ($movementTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $badgeColor = $movement->getTypeBadgeColor();

            // Property: Badge color should not be null or empty
            $this->assertNotNull(
                $badgeColor,
                "Movement type '{$type}' should have a defined badge color"
            );

            $this->assertNotEmpty(
                $badgeColor,
                "Movement type '{$type}' should have a non-empty badge color"
            );

            // Property: Badge color should be a valid Tailwind color class
            $this->assertMatchesRegularExpression(
                '/^bg-(blue|green|red|yellow|purple|gray|indigo|pink|orange)-(100|200|300|400|500|600|700|800|900)$/',
                $badgeColor,
                "Movement type '{$type}' should have a valid Tailwind badge color class. Got: {$badgeColor}"
            );

            // Clean up
            $movement->delete();
        }
    }
}
