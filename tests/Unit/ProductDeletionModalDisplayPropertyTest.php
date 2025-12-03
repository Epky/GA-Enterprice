<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 1: Modal Display Universality
 * Validates: Requirements 1.1
 */
class ProductDeletionModalDisplayPropertyTest extends TestCase
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
     * Property 1: Modal Display Universality
     * For any product in the system, clicking the delete button should trigger 
     * the confirmation modal to appear, regardless of the product's stock status.
     * 
     * @test
     */
    public function property_modal_displays_for_all_products_regardless_of_stock()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random product with random stock level
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random stock quantity (0 to 1000)
            $stockQuantity = rand(0, 1000);
            
            if ($stockQuantity > 0) {
                // Create inventory with random stock
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                ]);
            }

            // Act: Load the product list page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            // Assert: Page should contain the delete confirmation modal structure
            $response->assertStatus(200);
            $response->assertSee('x-on:open-delete-modal.window', false);
            $response->assertSee('Delete Product');
            
            // Assert: Modal should have product data attributes on delete button
            $response->assertSee('data-product-id="' . $product->id . '"', false);
            $response->assertSee($product->name);
            
            // Clean up for next iteration
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Property: Modal displays on product detail page
     * 
     * @test
     */
    public function property_modal_displays_on_detail_page_for_all_products()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange
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

            // Act: Load the product detail page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Modal structure should be present
            $response->assertStatus(200);
            $response->assertSee('x-on:open-delete-modal.window', false);
            $response->assertSee('Delete Product');
            $response->assertSee($product->name);
            
            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Property: Modal displays on product edit page
     * 
     * @test
     */
    public function property_modal_displays_on_edit_page_for_all_products()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange
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

            // Act: Load the product edit page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Modal structure should be present
            $response->assertStatus(200);
            $response->assertSee('x-on:open-delete-modal.window', false);
            $response->assertSee('Delete Product');
            $response->assertSee($product->name);
            
            // Clean up
            $product->inventory()->delete();
            $product->delete();
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
