<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 1: Product card completeness
 * Validates: Requirements 1.2
 */
class LandingPageProductCardCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: Product card completeness
     * For any product displayed in the grid, the rendered HTML should contain 
     * the product name, price, category name, and either a primary image URL 
     * or placeholder image indicator
     * 
     * @test
     */
    public function property_product_card_contains_all_required_elements()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with random attributes
            $hasImage = (bool) rand(0, 1);
            $hasCategory = (bool) rand(0, 1);
            
            $category = null;
            if ($hasCategory) {
                $category = Category::factory()->create(['is_active' => true]);
            }
            
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $hasCategory ? $category->id : null,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            if ($hasImage) {
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'image_url' => 'products/test-image-' . $iteration . '.jpg',
                    'is_primary' => true,
                ]);
            }
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Check that the product card contains all required elements
            $response->assertStatus(200);
            
            // 1. Product name should be present
            $response->assertSee($product->name, false);
            
            // 2. Price should be present (formatted with ₱ symbol)
            $formattedPrice = '₱' . number_format($product->base_price, 2);
            $response->assertSee($formattedPrice, false);
            
            // 3. Category name should be present (or "Uncategorized" if no category)
            if ($hasCategory) {
                $response->assertSee($category->name, false);
            } else {
                $response->assertSee('Uncategorized', false);
            }
            
            // 4. Either image or placeholder should be present
            if ($hasImage) {
                $response->assertSee('products/test-image-' . $iteration . '.jpg', false);
            } else {
                // Check for SVG placeholder icon
                $response->assertSee('<svg', false);
                $response->assertSee('h-20 w-20 text-gray-300', false);
            }
            
            // 5. View Details button/link should be present
            $response->assertSee('View Details', false);
        }
    }

    /**
     * Property: Product card with missing category shows "Uncategorized"
     * 
     * @test
     */
    public function property_product_card_shows_uncategorized_when_category_missing()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product without category
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => null,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert
            $response->assertStatus(200);
            $response->assertSee('Uncategorized', false);
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: All product cards in grid have consistent structure
     * 
     * @test
     */
    public function property_all_product_cards_have_consistent_structure()
    {
        // Run multiple iterations with varying product counts
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple products with varying attributes
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(3, 8);
            
            for ($i = 0; $i < $productCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
                
                // Randomly add images to some products
                if (rand(0, 1)) {
                    ProductImage::factory()->create([
                        'product_id' => $product->id,
                        'is_primary' => true,
                    ]);
                }
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Each product should have all required elements
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Count "View Details" buttons should match product count
            $viewDetailsCount = substr_count($content, 'View Details');
            $this->assertEquals($productCount, $viewDetailsCount,
                "Expected {$productCount} 'View Details' buttons, but found {$viewDetailsCount}");
        }
    }
}
