<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 7: No Warning for Zero Stock
 * Validates: Requirements 2.2
 */
class ProductDeletionNoWarningZeroStockPropertyTest extends TestCase
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
     * Property 7: No Warning for Zero Stock
     * For any product where total stock equals zero, the confirmation modal 
     * should display a standard confirmation message without stock warnings.
     * 
     * @test
     */
    public function property_modal_displays_no_warning_for_products_with_zero_stock()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create a random product with stock = 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'ZeroStockProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // Explicitly do NOT create any inventory records, ensuring stock = 0
            // Or create inventory with 0 quantity
            if (rand(0, 1) === 1) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                ]);
            }

            // Refresh to get computed total_stock
            $product->refresh();
            $actualStock = $product->total_stock;
            
            // Ensure stock is actually 0
            $this->assertEquals(0, $actualStock, 
                "Product should have stock = 0 for this test");

            // Act: Load the product list page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            // Assert: Page should NOT contain the warning message elements
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Check that warning message heading is NOT present
            $this->assertStringNotContainsString('Warning: Product has stock', $content,
                "Modal should NOT contain 'Warning: Product has stock' heading for products with stock = 0");
            
            // Check that stock quantity warning is NOT present
            $this->assertStringNotContainsString('units in stock across all locations', $content,
                "Modal should NOT display stock quantity warning for products with stock = 0");
            
            // Check that permanent removal warning is NOT present
            $this->assertStringNotContainsString('Deleting this product will permanently remove all stock records', $content,
                "Modal should NOT warn about permanent deletion of stock records for products with stock = 0");
            
            // Check that red warning styling is NOT present in the warning box context
            // Note: The delete button itself uses red styling, so we need to be specific
            // We're checking that the warning box (bg-red-50 border-red-200) is not present
            $warningBoxPattern = '/bg-red-50.*?border-red-200/s';
            $this->assertDoesNotMatchRegularExpression($warningBoxPattern, $content,
                "Modal should NOT use red warning box styling (bg-red-50 border-red-200) for products with stock = 0");
            
            // Check that standard confirmation message IS present
            $this->assertStringContainsString('This action cannot be undone', $content,
                "Modal should display standard confirmation message for products with stock = 0");
            $this->assertStringContainsString('This will permanently delete the product and all associated data', $content,
                "Modal should display standard deletion warning for products with stock = 0");
        }
    }

    /**
     * Property: No warning displays on product detail page for products with zero stock
     * 
     * @test
     */
    public function property_no_warning_displays_on_detail_page_for_zero_stock_products()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with stock = 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'DetailZeroStock ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // No inventory or zero inventory
            if (rand(0, 1) === 1) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                ]);
            }

            $product->refresh();
            $actualStock = $product->total_stock;
            
            $this->assertEquals(0, $actualStock);

            // Act: Load the product detail page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Warning message should NOT be present
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringNotContainsString('Warning: Product has stock', $content,
                "Detail page modal should NOT contain warning heading for zero stock");
            $this->assertStringNotContainsString('units in stock across all locations', $content,
                "Detail page modal should NOT display stock quantity warning for zero stock");
            
            // Standard confirmation should be present
            $this->assertStringContainsString('This action cannot be undone', $content,
                "Detail page modal should display standard confirmation for zero stock");
        }
    }

    /**
     * Property: No warning displays on product edit page for products with zero stock
     * 
     * @test
     */
    public function property_no_warning_displays_on_edit_page_for_zero_stock_products()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with stock = 0
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'EditZeroStock ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // No inventory or zero inventory
            if (rand(0, 1) === 1) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                ]);
            }

            $product->refresh();
            $actualStock = $product->total_stock;
            
            $this->assertEquals(0, $actualStock);

            // Act: Load the product edit page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Warning message should NOT be present
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringNotContainsString('Warning: Product has stock', $content,
                "Edit page modal should NOT contain warning heading for zero stock");
            $this->assertStringNotContainsString('units in stock across all locations', $content,
                "Edit page modal should NOT display stock quantity warning for zero stock");
            
            // Standard confirmation should be present
            $this->assertStringContainsString('This action cannot be undone', $content,
                "Edit page modal should display standard confirmation for zero stock");
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
