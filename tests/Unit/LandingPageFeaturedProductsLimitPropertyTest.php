<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Feature: landing-page-product-display, Property 4: Featured products limit
 * Validates: Requirements 2.2
 */
class LandingPageFeaturedProductsLimitPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 4: Featured products limit
     * For any set of featured products, the landing page should display 
     * a maximum of 4 featured products regardless of how many products 
     * are marked as featured
     * 
     * @test
     */
    public function property_featured_products_limited_to_maximum_of_four()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            DB::table('product_images')->delete();
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create random number of featured products (between 5 and 20)
            $featuredCount = rand(5, 20);
            
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count($featuredCount)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Check that only 4 featured products are displayed
            $response->assertStatus(200);
            
            // Count the number of "⭐ FEATURED" badges in the featured section
            $content = $response->getContent();
            
            // Extract the featured products section by looking for the heading and the next closing div
            if (strpos($content, '✨ Featured Products') !== false) {
                // Count featured badges in the entire response
                $featuredBadgeCount = substr_count($content, '⭐ FEATURED');
                
                // Should be exactly 4 featured products displayed
                $this->assertEquals(4, $featuredBadgeCount, 
                    "Expected exactly 4 featured products to be displayed, but found {$featuredBadgeCount} in iteration {$iteration}");
            } else {
                $this->fail("Featured products section not found in iteration {$iteration}");
            }
        }
    }

    /**
     * Property: When fewer than 4 featured products exist, all should be displayed
     * 
     * @test
     */
    public function property_all_featured_products_displayed_when_less_than_four()
    {
        // Test with 1, 2, and 3 featured products
        for ($featuredCount = 1; $featuredCount <= 3; $featuredCount++) {
            // Run multiple iterations for each count
            for ($iteration = 0; $iteration < 20; $iteration++) {
                // Clean up before each iteration
                DB::table('product_images')->delete();
                DB::table('products')->delete();
                DB::table('categories')->delete();
                DB::table('brands')->delete();
                DB::table('users')->delete();
                
                // Arrange
                $category = Category::factory()->create(['is_active' => true]);
                $brand = Brand::factory()->create(['is_active' => true]);
                
                Product::factory()->count($featuredCount)->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
                
                // Act
                $user = User::factory()->create(['role' => 'customer']);
                $response = $this->actingAs($user)->get(route('customer.dashboard'));
                
                // Assert
                $response->assertStatus(200);
                
                $content = $response->getContent();
                
                // Check if featured products section exists
                if (strpos($content, '✨ Featured Products') !== false) {
                    // Count featured badges
                    $featuredBadgeCount = substr_count($content, '⭐ FEATURED');
                    
                    $this->assertEquals($featuredCount, $featuredBadgeCount, 
                        "Expected {$featuredCount} featured products to be displayed, but found {$featuredBadgeCount}");
                } else {
                    $this->fail("Featured products section not found when {$featuredCount} featured products exist");
                }
            }
        }
    }

    /**
     * Property: When no featured products exist, featured section should not be displayed
     * 
     * @test
     */
    public function property_no_featured_section_when_no_featured_products()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create only non-featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count(rand(5, 15))->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert
            $response->assertStatus(200);
            $response->assertDontSee('✨ Featured Products', false);
        }
    }

    /**
     * Property: Featured products limit applies regardless of filters
     * 
     * @test
     */
    public function property_featured_products_limit_applies_with_filters()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create multiple categories with featured products
            $category1 = Category::factory()->create(['is_active' => true]);
            $category2 = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create 6 featured products in category 1
            Product::factory()->count(6)->create([
                'category_id' => $category1->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Create 3 featured products in category 2
            Product::factory()->count(3)->create([
                'category_id' => $category2->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act: Apply category filter
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', ['category' => $category1->id]));
            
            // Assert: Should still show max 4 featured products
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Check if featured products section exists
            if (strpos($content, '✨ Featured Products') !== false) {
                // Count featured badges
                $featuredBadgeCount = substr_count($content, '⭐ FEATURED');
                
                $this->assertLessThanOrEqual(4, $featuredBadgeCount, 
                    "Expected at most 4 featured products with filter, but found {$featuredBadgeCount}");
            }
        }
    }
}
