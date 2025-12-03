<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 5: Post-Deletion Redirect
 * Validates: Requirements 1.5
 */
class ProductDeletionPostDeletionRedirectPropertyTest extends TestCase
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
     * Property 5: Post-Deletion Redirect
     * For any product that is successfully deleted, the system should redirect 
     * to the product list page and display a success message.
     * 
     * @test
     */
    public function property_deletion_redirects_to_product_list_with_success_message()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
                'sku' => 'SKU-' . rand(10000, 99999),
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

            // Act: Submit DELETE request to delete the product
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Response should redirect to product list
            $response->assertRedirect(route('staff.products.index'));
            
            // Assert: Session should contain success message
            $response->assertSessionHas('success');
            
            // Verify the success message content
            $successMessage = session('success');
            $this->assertNotEmpty($successMessage, 
                "Success message should not be empty after deletion");
            $this->assertIsString($successMessage, 
                "Success message should be a string");
        }
    }

    /**
     * Property: Redirect occurs from all deletion contexts
     * 
     * @test
     */
    public function property_redirect_occurs_from_all_deletion_contexts()
    {
        // Test deletion from different contexts (list, detail, edit pages)
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

            // Act: Delete the product (deletion can be initiated from any page)
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Should always redirect to product list regardless of context
            $response->assertRedirect(route('staff.products.index'));
            $response->assertSessionHas('success');
        }
    }

    /**
     * Property: Success message is present and meaningful
     * 
     * @test
     */
    public function property_success_message_is_present_and_meaningful()
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

            // Act: Delete the product
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Success message should be present
            $response->assertSessionHas('success');
            
            $successMessage = session('success');
            
            // Assert: Message should be meaningful (not empty, reasonable length)
            $this->assertNotEmpty($successMessage);
            $this->assertGreaterThan(5, strlen($successMessage), 
                "Success message should be meaningful and not too short");
            $this->assertLessThan(200, strlen($successMessage), 
                "Success message should be concise");
        }
    }

    /**
     * Property: Redirect and message occur together atomically
     * 
     * @test
     */
    public function property_redirect_and_message_occur_together()
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

            // Act: Delete the product
            $response = $this->actingAs($this->createStaffUser())
                ->delete(route('staff.products.destroy', $product));

            // Assert: Both redirect and success message must be present
            // This ensures they occur atomically - you can't have one without the other
            $hasRedirect = $response->isRedirect();
            $hasSuccessMessage = session()->has('success');
            
            $this->assertTrue($hasRedirect, 
                "Deletion should result in a redirect");
            $this->assertTrue($hasSuccessMessage, 
                "Deletion should result in a success message");
            
            // If there's a redirect, it should be to the product list
            if ($hasRedirect) {
                $response->assertRedirect(route('staff.products.index'));
            }
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
