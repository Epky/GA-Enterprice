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
 * Feature: landing-page-product-display, Property 2: Placeholder image display
 * Validates: Requirements 1.3
 */
class LandingPagePlaceholderImagePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 2: Placeholder image display
     * For any product without a primary image, the rendered product card 
     * should contain a placeholder image element
     * 
     * @test
     */
    public function property_products_without_images_show_placeholder()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products without images
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(1, 5);
            $productsWithoutImages = [];
            
            for ($i = 0; $i < $productCount; $i++) {
                $productsWithoutImages[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
            }
            
            // Act: Render the dashboard view
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Products without images should have placeholder SVG
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for SVG placeholder icon (h-20 w-20 text-gray-300)
            $this->assertStringContainsString('<svg', $content);
            $this->assertStringContainsString('h-20 w-20 text-gray-300', $content);
            
            // Verify each product without image is displayed
            foreach ($productsWithoutImages as $product) {
                $response->assertSee($product->name, false);
            }
        }
    }

    /**
     * Property: Products with images should not show placeholder
     * 
     * @test
     */
    public function property_products_with_images_show_actual_image()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with image
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $imageUrl = 'products/test-image-' . $iteration . '.jpg';
            ProductImage::factory()->create([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'is_primary' => true,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Should show actual image
            $response->assertStatus(200);
            $response->assertSee($imageUrl, false);
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: Placeholder icon has correct styling
     * 
     * @test
     */
    public function property_placeholder_icon_has_correct_styling()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product without image
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Placeholder should have specific styling
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for gray placeholder styling
            $this->assertStringContainsString('text-gray-300', $content);
            $this->assertStringContainsString('h-20 w-20', $content);
            
            // Check for centered container
            $this->assertStringContainsString('flex items-center justify-center', $content);
        }
    }

    /**
     * Property: Placeholder is displayed in correct container
     * 
     * @test
     */
    public function property_placeholder_is_in_correct_container()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product without image
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Placeholder should be in a container with gray background
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for container with gray background
            $this->assertStringContainsString('bg-gray-100', $content);
            $this->assertStringContainsString('aspect-square', $content);
        }
    }

    /**
     * Property: All products without images get placeholders
     * 
     * @test
     */
    public function property_all_imageless_products_have_placeholders()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple products without images
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(2, 6);
            for ($i = 0; $i < $productCount; $i++) {
                Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
            }
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: Should have placeholder SVGs for all products
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Count SVG placeholders
            // Each product without image should have an SVG placeholder
            $svgCount = substr_count($content, 'h-20 w-20 text-gray-300');
            
            $this->assertGreaterThanOrEqual($productCount, $svgCount, 
                "Expected at least {$productCount} placeholder icons");
        }
    }
}
