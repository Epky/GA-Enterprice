<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementClassificationTest extends TestCase
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
     * Test isBusinessMovement() with all business types
     * Requirements: 1.1
     * 
     * @test
     */
    public function test_is_business_movement_returns_true_for_business_types()
    {
        $businessTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer'];

        foreach ($businessTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $this->assertTrue(
                $movement->isBusinessMovement(),
                "Movement type '{$type}' should be classified as a business movement"
            );

            $movement->delete();
        }
    }

    /**
     * Test isSystemMovement() with system types
     * Requirements: 1.1
     * 
     * @test
     */
    public function test_is_system_movement_returns_true_for_system_types()
    {
        $systemTypes = ['reservation', 'release'];

        foreach ($systemTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $this->assertTrue(
                $movement->isSystemMovement(),
                "Movement type '{$type}' should be classified as a system movement"
            );

            $movement->delete();
        }
    }

    /**
     * Test that business movements are not classified as system movements
     * Requirements: 1.1
     * 
     * @test
     */
    public function test_business_movements_are_not_system_movements()
    {
        $businessTypes = ['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer'];

        foreach ($businessTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $this->assertFalse(
                $movement->isSystemMovement(),
                "Movement type '{$type}' should not be classified as a system movement"
            );

            $movement->delete();
        }
    }

    /**
     * Test that system movements are not classified as business movements
     * Requirements: 1.1
     * 
     * @test
     */
    public function test_system_movements_are_not_business_movements()
    {
        $systemTypes = ['reservation', 'release'];

        foreach ($systemTypes as $type) {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => $type,
                'quantity' => fake()->numberBetween(-100, 100),
                'performed_by' => $this->user->id,
            ]);

            $this->assertFalse(
                $movement->isBusinessMovement(),
                "Movement type '{$type}' should not be classified as a business movement"
            );

            $movement->delete();
        }
    }

    /**
     * Test edge cases with unknown types
     * Requirements: 1.1
     * 
     * @test
     */
    public function test_unknown_movement_types_are_not_classified()
    {
        // Create a movement with a valid type first
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => fake()->numberBetween(-100, 100),
            'performed_by' => $this->user->id,
        ]);

        // Test unknown types by directly setting the attribute (bypassing database constraints)
        $unknownTypes = ['unknown', 'invalid', 'test', 'random'];

        foreach ($unknownTypes as $type) {
            $movement->movement_type = $type;

            $this->assertFalse(
                $movement->isBusinessMovement(),
                "Unknown movement type '{$type}' should not be classified as a business movement"
            );

            $this->assertFalse(
                $movement->isSystemMovement(),
                "Unknown movement type '{$type}' should not be classified as a system movement"
            );
        }

        $movement->delete();
    }
}
