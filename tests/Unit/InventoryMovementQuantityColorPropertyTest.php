<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementQuantityColorPropertyTest extends TestCase
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
     * Feature: inventory-movements-display-improvement, Property 8: Quantity color coding
     * Validates: Requirements 5.1
     * 
     * @test
     */
    public function property_positive_quantities_get_green_color_class()
    {
        // Run 100 iterations with random positive quantities
        for ($i = 0; $i < 100; $i++) {
            $quantity = fake()->numberBetween(1, 10000);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['purchase', 'return', 'adjustment']),
                'quantity' => $quantity,
                'performed_by' => $this->user->id,
            ]);

            // Property: positive quantities should get green color class
            $colorClass = $movement->getQuantityColorClass();

            $this->assertEquals(
                'text-green-600',
                $colorClass,
                "Iteration {$i}: Positive quantity {$quantity} should have green color class"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 8: Quantity color coding
     * Validates: Requirements 5.1
     * 
     * @test
     */
    public function property_negative_quantities_get_red_color_class()
    {
        // Run 100 iterations with random negative quantities
        for ($i = 0; $i < 100; $i++) {
            $quantity = fake()->numberBetween(-10000, -1);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['sale', 'damage', 'adjustment']),
                'quantity' => $quantity,
                'performed_by' => $this->user->id,
            ]);

            // Property: negative quantities should get red color class
            $colorClass = $movement->getQuantityColorClass();

            $this->assertEquals(
                'text-red-600',
                $colorClass,
                "Iteration {$i}: Negative quantity {$quantity} should have red color class"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 8: Quantity color coding
     * Edge case: Zero quantity
     * 
     * @test
     */
    public function property_zero_quantity_gets_neutral_color_class()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity' => 0,
            'performed_by' => $this->user->id,
        ]);

        // Property: zero quantity should get neutral color class
        $colorClass = $movement->getQuantityColorClass();

        $this->assertEquals(
            'text-gray-600',
            $colorClass,
            "Zero quantity should have neutral gray color class"
        );

        $movement->delete();
    }
}
