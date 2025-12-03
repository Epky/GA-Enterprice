<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 6: Stock Warning Display
 * Validates: Requirements 2.1
 */
class ProductDeletionStockWarningDisplayPropertyTest extends TestCase
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
     * Property 6: Stock Warning Display
     * For any product where total stock is greater than zero, the confirmation modal 
     * should display a warning message about stock loss.
     * 
     * @test
     */
    public function property_modal_displays_warning_for_products_with_stock()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create a random product with stock > 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // Generate random stock quantity (1 to 1000, ensuring > 0)
            $stockQuantity = rand(1, 1000);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $stockQuantity,
                'quantity_reserved' => 0,
            ]);

            // Refresh to get computed total_stock
            $product->refresh();
            $actualStock = $product->total_stock;
            
            // Ensure stock is actually > 0
            $this->assertGreaterThan(0, $actualStock, 
                "Product should have stock > 0 for this test");

            // Act: Load the product list page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            // Assert: Page should contain the warning message elements
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Check for warning message heading
            $this->assertStringContainsString('Warning: Product has stock', $content,
                "Modal should contain 'Warning: Product has stock' heading for products with stock > 0");
            
            // Check for stock quantity in warning message
            $this->assertStringContainsString($actualStock . ' units', $content,
                "Modal should display the stock quantity ({$actualStock} units) in the warning message");
            
            // Check for warning about permanent deletion
            $this->assertStringContainsString('Deleting this product will permanently remove all stock records', $content,
                "Modal should warn about permanent deletion of stock records");
            
            // Check for red warning styling (bg-red-50 border-red-200)
            $this->assertStringContainsString('bg-red-50', $content,
                "Modal should use red background styling for stock warning");
            $this->assertStringContainsString('border-red-200', $content,
                "Modal should use red border styling for stock warning");
        }
    }

    /**
     * Property: Warning displays on product detail page for products with stock
     * 
     * @test
     */
    public function property_warning_displays_on_detail_page_for_products_with_stock()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with stock > 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'DetailProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $stockQuantity = rand(1, 500);
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $stockQuantity,
                'quantity_reserved' => 0,
            ]);

            $product->refresh();
            $actualStock = $product->total_stock;
            
            $this->assertGreaterThan(0, $actualStock);

            // Act: Load the product detail page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Warning message should be present
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringContainsString('Warning: Product has stock', $content,
                "Detail page modal should contain warning heading");
            $this->assertStringContainsString($actualStock . ' units', $content,
                "Detail page modal should display stock quantity in warning");
            $this->assertStringContainsString('bg-red-50', $content,
                "Detail page modal should use red warning styling");
        }
    }

    /**
     * Property: Warning displays on product edit page for products with stock
     * 
     * @test
     */
    public function property_warning_displays_on_edit_page_for_products_with_stock()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with stock > 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'EditProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $stockQuantity = rand(1, 500);
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $stockQuantity,
                'quantity_reserved' => 0,
            ]);

            $product->refresh();
            $actualStock = $product->total_stock;
            
            $this->assertGreaterThan(0, $actualStock);

            // Act: Load the product edit page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Warning message should be present
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringContainsString('Warning: Product has stock', $content,
                "Edit page modal should contain warning heading");
            $this->assertStringContainsString($actualStock . ' units', $content,
                "Edit page modal should display stock quantity in warning");
            $this->assertStringContainsString('bg-red-50', $content,
                "Edit page modal should use red warning styling");
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
