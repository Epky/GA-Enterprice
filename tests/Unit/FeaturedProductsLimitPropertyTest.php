<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 1: Featured products limit
 * Validates: Requirements 2.2
 */
class FeaturedProductsLimitPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: Featured products limit
     * For any number of products marked as featured, the dashboard should display 
     * a maximum of 4 featured products
     * 
     * @test
     */
    public function property_featured_products_limited_to_maximum_of_four()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
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
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Check that only 4 featured products are displayed
            $response->assertStatus(200);
            
            // Count the number of "FEATURED" badges in the response
            $content = $response->getContent();
            $featuredBadgeCount = substr_count($content, 'FEATURED');
            
            // Should be exactly 4 featured products displayed
            $this->assertEquals(4, $featuredBadgeCount, 
                "Expected exactly 4 featured products to be displayed, but found {$featuredBadgeCount}");
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
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
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
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert
            $response->assertStatus(200);
            
            $content = $response->getContent();
            $featuredBadgeCount = substr_count($content, 'FEATURED');
            
            $this->assertEquals($featuredCount, $featuredBadgeCount, 
                "Expected {$featuredCount} featured products to be displayed, but found {$featuredBadgeCount}");
        }
    }

    /**
     * Property: When no featured products exist, featured section should not be displayed
     * 
     * @test
     */
    public function property_no_featured_section_when_no_featured_products()
    {
        // Arrange: Create only non-featured products
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Product::factory()->count(10)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertDontSee('✨ Featured Products', false);
        $response->assertDontSee('FEATURED', false);
    }
}
