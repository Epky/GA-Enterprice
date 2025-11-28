<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 2: Featured badge presence
 * Validates: Requirements 2.3
 */
class FeaturedBadgePresencePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2: Featured badge presence
     * For any product displayed in the featured section, the rendered HTML 
     * should contain a "FEATURED" badge element
     * 
     * @test
     */
    public function property_all_featured_products_have_featured_badge()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
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
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Each featured product should have a FEATURED badge
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            // Count FEATURED badges
            $badgeCount = substr_count($content, 'FEATURED');
            
            // Should have exactly one badge per featured product
            $this->assertEquals($featuredCount, $badgeCount, 
                "Expected {$featuredCount} FEATURED badges, but found {$badgeCount}");
            
            // Verify each product name appears with a FEATURED badge nearby
            foreach ($featuredProducts as $product) {
                $response->assertSee($product->name, false);
                $response->assertSee('FEATURED', false);
            }
        }
    }

    /**
     * Property: Featured badge has correct styling
     * 
     * @test
     */
    public function property_featured_badge_has_yellow_styling()
    {
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
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Badge should have yellow background styling
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check for yellow badge styling (bg-yellow-400 and text-yellow-900)
        $this->assertStringContainsString('bg-yellow-400', $content);
        $this->assertStringContainsString('text-yellow-900', $content);
        $this->assertStringContainsString('FEATURED', $content);
    }

    /**
     * Property: Non-featured products should not have FEATURED badge
     * 
     * @test
     */
    public function property_non_featured_products_have_no_featured_badge()
    {
        // Arrange: Create only non-featured products
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: No FEATURED badges should be present
        $response->assertStatus(200);
        $response->assertDontSee('FEATURED', false);
    }

    /**
     * Property: Featured badge is positioned on the product image
     * 
     * @test
     */
    public function property_featured_badge_is_positioned_correctly()
    {
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
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Badge should be in an absolute positioned container
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check for absolute positioning classes (top-3 right-3)
        $this->assertStringContainsString('absolute', $content);
        $this->assertStringContainsString('top-3', $content);
        $this->assertStringContainsString('right-3', $content);
    }
}
