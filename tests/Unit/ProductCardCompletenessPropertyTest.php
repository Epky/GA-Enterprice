<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 8: Product card completeness
 * Validates: Requirements 4.1
 */
class ProductCardCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 8: Product card completeness
     * For any product displayed in the grid, the product card should contain all 
     * required elements: image/placeholder, category name, product name, price, and action button
     * 
     * @test
     */
    public function property_product_card_contains_all_required_elements()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            \DB::table('product_images')->delete();
            
            // Arrange: Create product with random attributes
            $hasImage = (bool) rand(0, 1);
            
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            if ($hasImage) {
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'image_url' => 'products/test-image.jpg',
                    'is_primary' => true,
                ]);
            }
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Check that the product card contains all required elements
            $response->assertStatus(200);
            
            // 1. Category name should be present
            $response->assertSee($category->name, false);
            
            // 2. Product name should be present
            $response->assertSee($product->name, false);
            
            // 3. Price should be present (formatted with ₱ symbol)
            $formattedPrice = '₱' . number_format($product->base_price, 2);
            $response->assertSee($formattedPrice, false);
            
            // 4. Action button should be present
            $response->assertSee('View Details', false);
            
            // 5. Either image or placeholder should be present
            if ($hasImage) {
                $response->assertSee('products/test-image.jpg', false);
            } else {
                // Check for SVG placeholder icon
                $response->assertSee('<svg', false);
                $response->assertSee('h-20 w-20 text-gray-300', false);
            }
        }
    }

    /**
     * Property: Product card with missing category should show "Uncategorized"
     * 
     * @test
     */
    public function property_product_card_shows_uncategorized_when_category_missing()
    {
        // Arrange: Create product without category
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => null,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Uncategorized', false);
    }

    /**
     * Property: All product cards in grid should have consistent structure
     * 
     * @test
     */
    public function property_all_product_cards_have_consistent_structure()
    {
        // Arrange: Create multiple products with varying attributes
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productCount = rand(3, 6);
        
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
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Each product should have all required elements
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Count "View Details" buttons should match product count
        $viewDetailsCount = substr_count($content, 'View Details');
        $this->assertEquals($productCount, $viewDetailsCount,
            "Expected {$productCount} 'View Details' buttons, but found {$viewDetailsCount}");
    }
}
