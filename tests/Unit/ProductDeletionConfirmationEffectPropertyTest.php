<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 3: Deletion Confirmation Effect
 * Validates: Requirements 1.3
 */
class ProductDeletionConfirmationEffectPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    /**
     * Property 3: Deletion Confirmation Effect
     * For any product, when deletion is confirmed through the modal, the product 
     * should no longer exist in the database after the operation completes.
     * 
     * @test
     */
    public function property_product_does_not_exist_after_confirmed_deletion()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // Generate random stock quantity (0 to 1000)
            $stockQuantity = rand(0, 1000);
            
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                    'quantity_reserved' => 0,
                ]);
            }

            $productId = $product->id;

            // Assert: Product exists before deletion
            $this->assertDatabaseHas('products', ['id' => $productId]);

            // Act: Simulate confirmed deletion by submitting DELETE request
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Response should redirect to product list
            $response->assertRedirect(route('staff.products.index'));
            $response->assertSessionHas('success', 'Product deleted successfully.');

            // Assert: Product should be soft deleted
            $this->assertSoftDeleted('products', ['id' => $productId]);
            
            // Verify product cannot be found without trashed
            $this->assertNull(Product::find($productId), 
                "Product with ID {$productId} should not be accessible after deletion");
        }
    }

    /**
     * Property: Deletion removes product from all pages
     * 
     * @test
     */
    public function property_deleted_product_not_accessible_from_any_page()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            $stockQuantity = rand(0, 500);
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                ]);
            }

            $productId = $product->id;
            $productName = $product->name;

            // Act: Delete the product
            $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Product should not appear in product list
            $listResponse = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));
            $listResponse->assertStatus(200);
            $listResponse->assertDontSee($productName);

            // Assert: Accessing product detail page should return 404
            $detailResponse = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $productId));
            $detailResponse->assertStatus(404);

            // Assert: Accessing product edit page should return 404
            $editResponse = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $productId));
            $editResponse->assertStatus(404);
        }
    }

    /**
     * Property: Deletion works regardless of stock level
     * 
     * @test
     */
    public function property_deletion_succeeds_regardless_of_stock_level()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create products with varying stock levels
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Test with different stock scenarios
            $stockScenarios = [
                0,                    // No stock
                rand(1, 10),         // Low stock
                rand(11, 100),       // Medium stock
                rand(101, 1000),     // High stock
            ];

            $stockQuantity = $stockScenarios[array_rand($stockScenarios)];
            
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                ]);
            }

            $productId = $product->id;

            // Act: Delete the product
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Deletion should succeed regardless of stock level
            $response->assertRedirect(route('staff.products.index'));
            $response->assertSessionHas('success');
            $this->assertSoftDeleted('products', ['id' => $productId]);
        }
    }

    /**
     * Helper method to create a staff user
     */
    private function createStaffUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'staff',
        ]);
    }
}
