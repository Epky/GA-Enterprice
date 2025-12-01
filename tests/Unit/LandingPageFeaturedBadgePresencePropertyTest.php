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
 * Feature: landing-page-product-display, Property 5: Featured badge presence
 * Validates: Requirements 2.3
 */
class LandingPageFeaturedBadgePresencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 5: Featured badge presence
     * For any product marked as featured and displayed in the featured section, 
     * the rendered HTML should contain a featured badge indicator
     * 
     * @test
     */
    public function property_all_featured_products_have_featured_badge()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            DB::table('product_images')->delete();
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create random number of featured products (1 to 4)
            $featuredCount = rand(1, 4);
            
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredProducts = Product::factory()->count($featuredCount)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Each featured product should have a FEATURED badge
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Count FEATURED badges
            $badgeCount = substr_count($content, '⭐ FEATURED');
            
            // Should have exactly one badge per featured product
            $this->assertEquals($featuredCount, $badgeCount, 
                "Expected {$featuredCount} FEATURED badges, but found {$badgeCount} in iteration {$iteration}");
            
            // Verify each product name appears in the response
            foreach ($featuredProducts as $product) {
                $response->assertSee($product->name, false);
            }
        }
    }

    /**
     * Property: Featured badge has correct styling (yellow/orange gradient)
     * 
     * @test
     */
    public function property_featured_badge_has_correct_styling()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Badge should have yellow/orange gradient styling
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for gradient badge styling (from-yellow-400 to-orange-400)
            $this->assertStringContainsString('from-yellow-400', $content);
            $this->assertStringContainsString('to-orange-400', $content);
            $this->assertStringContainsString('⭐ FEATURED', $content);
        }
    }

    /**
     * Property: Non-featured products should not have FEATURED badge
     * 
     * @test
     */
    public function property_non_featured_products_have_no_featured_badge()
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
            
            Product::factory()->count(rand(5, 10))->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: No FEATURED badges should be present
            $response->assertStatus(200);
            $response->assertDontSee('⭐ FEATURED', false);
        }
    }

    /**
     * Property: Featured badge is positioned on the product image (top-right)
     * 
     * @test
     */
    public function property_featured_badge_is_positioned_correctly()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Badge should be in an absolute positioned container
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for absolute positioning classes (top-3 right-3)
            $this->assertStringContainsString('absolute', $content);
            $this->assertStringContainsString('top-3', $content);
            $this->assertStringContainsString('right-3', $content);
        }
    }

    /**
     * Property: Featured badge appears only in featured section, not in main grid
     * 
     * @test
     */
    public function property_featured_badge_only_in_featured_section()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products and non-featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(2, 4);
            
            // Create featured products
            Product::factory()->count($featuredCount)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Create non-featured products
            Product::factory()->count(rand(5, 10))->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Badge count should match featured product count
            $response->assertStatus(200);
            $content = $response->getContent();
            
            $badgeCount = substr_count($content, '⭐ FEATURED');
            
            $this->assertEquals($featuredCount, $badgeCount, 
                "Expected {$featuredCount} FEATURED badges (only in featured section), but found {$badgeCount}");
        }
    }

    /**
     * Property: Featured badge text is consistent across all featured products
     * 
     * @test
     */
    public function property_featured_badge_text_is_consistent()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create multiple featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(2, 4);
            
            Product::factory()->count($featuredCount)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All badges should have the exact same text
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Count the badge text occurrences
            $badgeCount = substr_count($content, '⭐ FEATURED');
            
            // All badges should be present
            $this->assertEquals($featuredCount, $badgeCount, 
                "Expected {$featuredCount} consistent FEATURED badges, but found {$badgeCount}");
            
            // Verify the badge styling is consistent
            $this->assertStringContainsString('from-yellow-400', $content);
            $this->assertStringContainsString('to-orange-400', $content);
        }
    }
}
