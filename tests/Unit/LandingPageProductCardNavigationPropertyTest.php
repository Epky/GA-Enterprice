<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 3: Product card navigation
 * Validates: Requirements 1.5
 */
class LandingPageProductCardNavigationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 3: Product card navigation
     * For any product displayed in the grid, the product card should contain 
     * a valid link to that product's detail page
     * 
     * @test
     */
    public function property_product_cards_contain_valid_navigation_links()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(1, 5);
            $products = [];
            
            for ($i = 0; $i < $productCount; $i++) {
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
            }
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Each product should have a link to its detail page
            $response->assertStatus(200);
            
            foreach ($products as $product) {
                // Check that the product detail route exists in the response
                $expectedUrl = route('products.show', $product);
                $response->assertSee($expectedUrl, false);
                
                // Also verify the product name is present
                $response->assertSee($product->name, false);
            }
        }
    }

    /**
     * Property: Product card links are wrapped in anchor tags
     * 
     * @test
     */
    public function property_product_card_links_are_anchor_tags()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Link should be in an anchor tag
            $response->assertStatus(200);
            $content = $response->getContent();
            
            $expectedUrl = route('products.show', $product);
            $this->assertStringContainsString('<a href="' . $expectedUrl . '"', $content);
        }
    }

    /**
     * Property: All product cards have clickable "View Details" buttons
     * 
     * @test
     */
    public function property_all_product_cards_have_view_details_buttons()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(2, 6);
            $products = [];
            
            for ($i = 0; $i < $productCount; $i++) {
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Each product should have a "View Details" button
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Count "View Details" buttons should match product count
            $viewDetailsCount = substr_count($content, 'View Details');
            $this->assertEquals($productCount, $viewDetailsCount,
                "Expected {$productCount} 'View Details' buttons, but found {$viewDetailsCount}");
            
            // Verify each product has its link
            foreach ($products as $product) {
                $expectedUrl = route('products.show', $product);
                $response->assertSee($expectedUrl, false);
            }
        }
    }

    /**
     * Property: Product detail routes exist for all products
     * 
     * @test
     */
    public function property_product_detail_routes_exist()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act & Assert: Verify the route exists and is valid
            $this->assertTrue(
                \Route::has('products.show'),
                'Product detail route should exist'
            );
            
            // Verify we can generate a URL for this product
            $url = route('products.show', $product);
            $this->assertNotEmpty($url);
            $this->assertStringContainsString((string)$product->id, $url);
        }
    }

    /**
     * Property: Navigation links are consistent across all product cards
     * 
     * @test
     */
    public function property_navigation_links_are_consistent()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(3, 8);
            $products = [];
            
            for ($i = 0; $i < $productCount; $i++) {
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All products should have consistent link structure
            $response->assertStatus(200);
            
            foreach ($products as $product) {
                $expectedUrl = route('products.show', $product);
                
                // Each product should have its unique URL
                $response->assertSee($expectedUrl, false);
                
                // URL should follow the same pattern
                $this->assertStringContainsString('/products/', $expectedUrl);
                $this->assertStringContainsString((string)$product->id, $expectedUrl);
            }
        }
    }
}
