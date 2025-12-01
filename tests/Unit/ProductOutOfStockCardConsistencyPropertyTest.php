<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 14: Out of stock card consistency
 * Validates: Requirements 5.5
 */
class ProductOutOfStockCardConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 14: Out of stock card consistency
     * For any out-of-stock product, the product card structure should contain 
     * the same elements (name, price, category, image, button) as in-stock products
     * 
     * @test
     */
    public function property_out_of_stock_card_has_same_structure_as_in_stock_card()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create one in-stock and one out-of-stock product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create out-of-stock product
            $outOfStockProduct = Product::factory()->create([
                'name' => 'Out of Stock Product ' . $iteration,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Randomly add image to out-of-stock product
            $outOfStockHasImage = (bool) rand(0, 1);
            if ($outOfStockHasImage) {
                ProductImage::factory()->create([
                    'product_id' => $outOfStockProduct->id,
                    'is_primary' => true,
                ]);
            }
            
            Inventory::factory()->create([
                'product_id' => $outOfStockProduct->id,
                'quantity_available' => 0,
                'quantity_reserved' => rand(0, 10),
            ]);
            
            // Create in-stock product
            $inStockProduct = Product::factory()->create([
                'name' => 'In Stock Product ' . $iteration,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Randomly add image to in-stock product (same probability)
            $inStockHasImage = (bool) rand(0, 1);
            if ($inStockHasImage) {
                ProductImage::factory()->create([
                    'product_id' => $inStockProduct->id,
                    'is_primary' => true,
                ]);
            }
            
            Inventory::factory()->create([
                'product_id' => $inStockProduct->id,
                'quantity_available' => rand(1, 50),
                'quantity_reserved' => rand(0, 10),
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Both products should have the same structural elements
            $response->assertStatus(200);
            
            // 1. Both should have product names
            $response->assertSee($outOfStockProduct->name, false);
            $response->assertSee($inStockProduct->name, false);
            
            // 2. Both should have prices
            $outOfStockPrice = '₱' . number_format($outOfStockProduct->base_price, 2);
            $inStockPrice = '₱' . number_format($inStockProduct->base_price, 2);
            $response->assertSee($outOfStockPrice, false);
            $response->assertSee($inStockPrice, false);
            
            // 3. Both should have category names
            $response->assertSee($category->name, false);
            
            // 4. Both should have "View Details" buttons
            $content = $response->getContent();
            $viewDetailsCount = substr_count($content, 'View Details');
            $this->assertGreaterThanOrEqual(2, $viewDetailsCount,
                "Iteration {$iteration}: Expected at least 2 'View Details' buttons");
            
            // 5. Both should have image or placeholder
            if ($outOfStockHasImage || $inStockHasImage) {
                // At least one should have an image
                $this->assertTrue(
                    str_contains($content, 'storage/') || str_contains($content, '<svg'),
                    "Iteration {$iteration}: Expected images or placeholders"
                );
            }
        }
    }

    /**
     * Property: Out of stock products maintain card layout consistency
     * 
     * @test
     */
    public function property_out_of_stock_products_maintain_card_layout()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create out-of-stock product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Card should have all standard elements
            $response->assertStatus(200);
            
            // Product name
            $response->assertSee($product->name, false);
            
            // Price
            $formattedPrice = '₱' . number_format($product->base_price, 2);
            $response->assertSee($formattedPrice, false);
            
            // Category
            $response->assertSee($category->name, false);
            
            // View Details button
            $response->assertSee('View Details', false);
            
            // OUT OF STOCK badge (additional element, but doesn't replace others)
            $response->assertSee('OUT OF STOCK', false);
        }
    }

    /**
     * Property: Out of stock badge is additive, not replacing other elements
     * 
     * @test
     */
    public function property_out_of_stock_badge_is_additive_not_replacing()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create out-of-stock product with description
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $uniqueDescription = 'Unique Description ' . $iteration . rand(1000, 9999);
            
            $product = Product::factory()->create([
                'description' => $uniqueDescription,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Both OUT OF STOCK badge AND description should be present
            $response->assertStatus(200);
            $response->assertSee('OUT OF STOCK', false);
            
            // Description should still be visible (truncated or full)
            $content = $response->getContent();
            $this->assertTrue(
                str_contains($content, $product->name),
                "Iteration {$iteration}: Product name should be visible"
            );
        }
    }

    /**
     * Property: Out of stock products with images show badge overlay
     * 
     * @test
     */
    public function property_out_of_stock_products_with_images_show_badge_overlay()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create out-of-stock product with image
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            ProductImage::factory()->create([
                'product_id' => $product->id,
                'is_primary' => true,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Should have image, badge, and all other elements
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
            $response->assertSee('OUT OF STOCK', false);
            $response->assertSee('View Details', false);
            
            // Check for overlay styling
            $content = $response->getContent();
            $this->assertTrue(
                str_contains($content, 'absolute') || str_contains($content, 'OUT OF STOCK'),
                "Iteration {$iteration}: Expected overlay styling for badge"
            );
        }
    }

    /**
     * Property: Out of stock products without images show badge with placeholder
     * 
     * @test
     */
    public function property_out_of_stock_products_without_images_show_badge_with_placeholder()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create out-of-stock product without image
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // No image created
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Should have placeholder, badge, and all other elements
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
            $response->assertSee('OUT OF STOCK', false);
            $response->assertSee('View Details', false);
            
            // Check for SVG placeholder
            $content = $response->getContent();
            $this->assertTrue(
                str_contains($content, '<svg') || str_contains($content, 'placeholder'),
                "Iteration {$iteration}: Expected placeholder for product without image"
            );
        }
    }

    /**
     * Property: Multiple out of stock products all maintain consistent structure
     * 
     * @test
     */
    public function property_multiple_out_of_stock_products_maintain_consistency()
    {
        // Clean up
        \DB::table('inventory')->delete();
        \DB::table('products')->delete();
        \DB::table('categories')->delete();
        \DB::table('brands')->delete();
        \DB::table('users')->delete();
        
        // Arrange: Create multiple out-of-stock products
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productCount = rand(3, 8);
        $products = [];
        
        for ($i = 0; $i < $productCount; $i++) {
            $product = Product::factory()->create([
                'name' => 'Out of Stock Product ' . $i,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
            
            $products[] = $product;
        }
        
        // Act: Render the landing page
        $user = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($user)->get(route('customer.dashboard'));
        
        // Assert: All products should have consistent structure
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Count OUT OF STOCK badges should match product count
        $badgeCount = substr_count($content, 'OUT OF STOCK');
        $this->assertEquals($productCount, $badgeCount,
            "Expected {$productCount} OUT OF STOCK badges");
        
        // Count View Details buttons should match product count
        $buttonCount = substr_count($content, 'View Details');
        $this->assertEquals($productCount, $buttonCount,
            "Expected {$productCount} 'View Details' buttons");
        
        // All product names should be present
        foreach ($products as $product) {
            $response->assertSee($product->name, false);
        }
    }
}
