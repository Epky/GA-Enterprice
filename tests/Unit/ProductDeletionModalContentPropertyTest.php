<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 2: Modal Content Completeness
 * Validates: Requirements 1.2
 */
class ProductDeletionModalContentPropertyTest extends TestCase
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
     * Property 2: Modal Content Completeness
     * For any product, when the confirmation modal is displayed, it should contain 
     * both the product name and the current total stock quantity.
     * 
     * @test
     */
    public function property_modal_contains_product_name_and_stock_quantity()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create a random product with random name and stock
            $randomName = 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999);
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => $randomName,
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

            // Refresh to get computed total_stock
            $product->refresh();
            $actualStock = $product->total_stock;

            // Act: Load the product list page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            // Assert: Page should contain the product name
            $response->assertStatus(200);
            $response->assertSee($randomName);
            
            // Assert: Delete button should have data attributes with product name and stock
            $content = $response->getContent();
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $content,
                "Delete button should have data-product-name attribute with value: {$randomName}");
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $content,
                "Delete button should have data-stock-quantity attribute with value: {$actualStock}");
        }
    }

    /**
     * Property: Modal content completeness on detail page
     * 
     * @test
     */
    public function property_modal_contains_correct_data_on_detail_page()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange
            $randomName = 'DetailProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999);
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => $randomName,
            ]);

            $stockQuantity = rand(0, 500);
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                    'quantity_reserved' => 0,
                ]);
            }

            // Refresh product to get computed total_stock attribute
            $product->refresh();
            $actualStock = $product->total_stock;

            // Act: Load the product detail page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Page should contain product name
            $response->assertStatus(200);
            $response->assertSee($randomName);
            
            // Assert: Delete button should have data attributes
            // On detail page, the delete button is in a dropdown menu
            $content = $response->getContent();
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $content, 
                "Delete button should have data-product-name attribute with value: {$randomName}");
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $content,
                "Delete button should have data-stock-quantity attribute with value: {$actualStock}");
        }
    }

    /**
     * Property: Modal content completeness on edit page
     * 
     * @test
     */
    public function property_modal_contains_correct_data_on_edit_page()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange
            $randomName = 'EditProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999);
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => $randomName,
            ]);

            $stockQuantity = rand(0, 500);
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                    'quantity_reserved' => 0,
                ]);
            }

            // Refresh product to get computed total_stock attribute
            $product->refresh();
            $actualStock = $product->total_stock;

            // Act: Load the product edit page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Page should contain product name
            $response->assertStatus(200);
            $response->assertSee($randomName);
            
            // Assert: Delete button should have data attributes
            // On edit page, the delete button is in the header
            $content = $response->getContent();
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $content,
                "Delete button should have data-product-name attribute with value: {$randomName}");
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $content,
                "Delete button should have data-stock-quantity attribute with value: {$actualStock}");
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
