<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Feature: landing-page-product-display, Property 6: Featured product card consistency
 * Validates: Requirements 2.5
 */
class LandingPageFeaturedProductCardConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 6: Featured product card consistency
     * For any featured product, the product card should contain the same 
     * data fields (name, price, category, image) as regular product cards
     * 
     * @test
     */
    public function property_featured_cards_have_same_fields_as_regular_cards()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            DB::table('product_images')->delete();
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured and non-featured products with random attributes
            $hasImage = (bool) rand(0, 1);
            $hasCategory = (bool) rand(0, 1);
            
            $category = null;
            if ($hasCategory) {
                $category = Category::factory()->create(['is_active' => true]);
            }
            
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create a featured product
            $featuredProduct = Product::factory()->create([
                'category_id' => $hasCategory ? $category->id : null,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Create a non-featured product with same attributes
            $regularProduct = Product::factory()->create([
                'category_id' => $hasCategory ? $category->id : null,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            if ($hasImage) {
                ProductImage::factory()->create([
                    'product_id' => $featuredProduct->id,
                    'image_url' => 'products/featured-' . $iteration . '.jpg',
                    'is_primary' => true,
                ]);
                
                ProductImage::factory()->create([
                    'product_id' => $regularProduct->id,
                    'image_url' => 'products/regular-' . $iteration . '.jpg',
                    'is_primary' => true,
                ]);
            }
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Both cards should have the same data fields
            $response->assertStatus(200);
            
            // 1. Both should have product names
            $response->assertSee($featuredProduct->name, false);
            $response->assertSee($regularProduct->name, false);
            
            // 2. Both should have prices
            $featuredPrice = '₱' . number_format($featuredProduct->base_price, 2);
            $regularPrice = '₱' . number_format($regularProduct->base_price, 2);
            $response->assertSee($featuredPrice, false);
            $response->assertSee($regularPrice, false);
            
            // 3. Both should have category names (or "Uncategorized")
            if ($hasCategory) {
                $response->assertSee($category->name, false);
            } else {
                $response->assertSee('Uncategorized', false);
            }
            
            // 4. Both should have images or placeholders
            if ($hasImage) {
                $response->assertSee('products/featured-' . $iteration . '.jpg', false);
                $response->assertSee('products/regular-' . $iteration . '.jpg', false);
            } else {
                // Check for SVG placeholder icon
                $response->assertSee('<svg', false);
                $response->assertSee('h-20 w-20 text-gray-300', false);
            }
        }
    }

    /**
     * Property: Featured cards have product name field
     * 
     * @test
     */
    public function property_featured_cards_contain_product_name()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(1, 4);
            $featuredProducts = [];
            
            for ($i = 0; $i < $featuredCount; $i++) {
                $featuredProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All featured products should have their names displayed
            $response->assertStatus(200);
            
            foreach ($featuredProducts as $product) {
                $response->assertSee($product->name, false);
            }
        }
    }

    /**
     * Property: Featured cards have price field
     * 
     * @test
     */
    public function property_featured_cards_contain_price()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(1, 4);
            $featuredProducts = [];
            
            for ($i = 0; $i < $featuredCount; $i++) {
                $featuredProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All featured products should have their prices displayed
            $response->assertStatus(200);
            
            foreach ($featuredProducts as $product) {
                $formattedPrice = '₱' . number_format($product->base_price, 2);
                $response->assertSee($formattedPrice, false);
            }
        }
    }

    /**
     * Property: Featured cards have category field
     * 
     * @test
     */
    public function property_featured_cards_contain_category()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products with and without categories
            $hasCategory = (bool) rand(0, 1);
            
            $category = null;
            if ($hasCategory) {
                $category = Category::factory()->create(['is_active' => true]);
            }
            
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(1, 4);
            
            for ($i = 0; $i < $featuredCount; $i++) {
                Product::factory()->create([
                    'category_id' => $hasCategory ? $category->id : null,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All featured products should have category or "Uncategorized"
            $response->assertStatus(200);
            
            if ($hasCategory) {
                $response->assertSee($category->name, false);
            } else {
                $response->assertSee('Uncategorized', false);
            }
        }
    }

    /**
     * Property: Featured cards have image or placeholder
     * 
     * @test
     */
    public function property_featured_cards_contain_image_or_placeholder()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('product_images')->delete();
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products with and without images
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $hasImage = (bool) rand(0, 1);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            if ($hasImage) {
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'image_url' => 'products/featured-test-' . $iteration . '.jpg',
                    'is_primary' => true,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Should have image or placeholder
            $response->assertStatus(200);
            
            if ($hasImage) {
                $response->assertSee('products/featured-test-' . $iteration . '.jpg', false);
            } else {
                // Check for SVG placeholder
                $response->assertSee('<svg', false);
                $response->assertSee('h-20 w-20 text-gray-300', false);
            }
        }
    }

    /**
     * Property: Featured cards have navigation link (View button)
     * 
     * @test
     */
    public function property_featured_cards_contain_navigation_link()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create featured products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $featuredCount = rand(1, 4);
            $featuredProducts = [];
            
            for ($i = 0; $i < $featuredCount; $i++) {
                $featuredProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All featured products should have navigation links
            $response->assertStatus(200);
            
            foreach ($featuredProducts as $product) {
                $expectedUrl = route('products.show', $product);
                $response->assertSee($expectedUrl, false);
            }
        }
    }

    /**
     * Property: Featured and regular cards have consistent structure
     * 
     * @test
     */
    public function property_featured_and_regular_cards_have_consistent_structure()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            DB::table('products')->delete();
            DB::table('categories')->delete();
            DB::table('brands')->delete();
            DB::table('users')->delete();
            
            // Arrange: Create both featured and regular products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create 2 featured products
            Product::factory()->count(2)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
            
            // Create 3 regular products
            Product::factory()->count(3)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Both types should have consistent elements
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Both should have category labels
            $categoryLabelCount = substr_count($content, $category->name);
            $this->assertGreaterThanOrEqual(5, $categoryLabelCount, 
                "Expected at least 5 category labels (2 featured + 3 regular)");
            
            // Both should have price symbols
            $priceSymbolCount = substr_count($content, '₱');
            $this->assertGreaterThanOrEqual(5, $priceSymbolCount, 
                "Expected at least 5 price symbols (2 featured + 3 regular)");
        }
    }
}
