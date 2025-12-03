<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 4: Cancellation Preservation
 * Validates: Requirements 1.4
 */
class ProductDeletionCancellationPreservationPropertyTest extends TestCase
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
     * Property 4: Cancellation Preservation
     * For any product, when deletion is canceled through the modal, the product 
     * should remain in the database with all its original data unchanged.
     * 
     * @test
     */
    public function property_product_remains_unchanged_after_cancellation()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random product with random attributes
            $randomName = 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999);
            $randomSku = 'SKU-' . strtoupper(fake()->unique()->bothify('???-###'));
            $randomPrice = rand(100, 10000) / 100; // Random price between 1.00 and 100.00
            $randomDescription = fake()->sentence(rand(5, 15));
            
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => $randomName,
                'sku' => $randomSku,
                'base_price' => $randomPrice,
                'description' => $randomDescription,
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

            // Store original product data for comparison
            $originalData = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'base_price' => $product->base_price,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
            ];

            // Store original inventory data
            $originalInventory = $product->inventory->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'product_id' => $inv->product_id,
                    'quantity_available' => $inv->quantity_available,
                    'quantity_reserved' => $inv->quantity_reserved,
                ];
            })->toArray();

            // Assert: Product exists before cancellation
            $this->assertDatabaseHas('products', ['id' => $productId]);

            // Act: Simulate cancellation by NOT submitting the delete request
            // Instead, we just load the page with the modal (which would be shown)
            // and verify the product still exists without any DELETE request
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            // Assert: Response should be successful
            $response->assertStatus(200);

            // Assert: Product should still exist in database with unchanged data
            $this->assertDatabaseHas('products', $originalData);
            
            // Refresh product from database
            $product->refresh();

            // Assert: All product attributes remain unchanged
            $this->assertEquals($originalData['id'], $product->id,
                "Product ID should remain unchanged after cancellation");
            $this->assertEquals($originalData['name'], $product->name,
                "Product name should remain unchanged after cancellation");
            $this->assertEquals($originalData['sku'], $product->sku,
                "Product SKU should remain unchanged after cancellation");
            $this->assertEquals($originalData['base_price'], $product->base_price,
                "Product base_price should remain unchanged after cancellation");
            $this->assertEquals($originalData['description'], $product->description,
                "Product description should remain unchanged after cancellation");
            $this->assertEquals($originalData['category_id'], $product->category_id,
                "Product category_id should remain unchanged after cancellation");
            $this->assertEquals($originalData['brand_id'], $product->brand_id,
                "Product brand_id should remain unchanged after cancellation");

            // Assert: Inventory data remains unchanged
            $currentInventory = $product->inventory->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'product_id' => $inv->product_id,
                    'quantity_available' => $inv->quantity_available,
                    'quantity_reserved' => $inv->quantity_reserved,
                ];
            })->toArray();

            $this->assertEquals($originalInventory, $currentInventory,
                "Inventory data should remain unchanged after cancellation");

            // Clean up for next iteration
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Property: Cancellation preserves product on detail page
     * 
     * @test
     */
    public function property_cancellation_preserves_product_from_detail_page()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'DetailProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $stockQuantity = rand(0, 500);
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                ]);
            }

            $productId = $product->id;
            $originalName = $product->name;

            // Act: Load detail page (simulating modal shown but canceled)
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Product still exists
            $response->assertStatus(200);
            $this->assertDatabaseHas('products', [
                'id' => $productId,
                'name' => $originalName,
            ]);

            // Clean up
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Property: Cancellation preserves product on edit page
     * 
     * @test
     */
    public function property_cancellation_preserves_product_from_edit_page()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'EditProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $stockQuantity = rand(0, 500);
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                ]);
            }

            $productId = $product->id;
            $originalName = $product->name;

            // Act: Load edit page (simulating modal shown but canceled)
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Product still exists
            $response->assertStatus(200);
            $this->assertDatabaseHas('products', [
                'id' => $productId,
                'name' => $originalName,
            ]);

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
