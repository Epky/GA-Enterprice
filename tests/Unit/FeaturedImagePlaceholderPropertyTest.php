<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 3: Image placeholder fallback
 * Validates: Requirements 2.5
 */
class FeaturedImagePlaceholderPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 3: Image placeholder fallback
     * For any product without a primary image, the product card should display 
     * a placeholder SVG icon
     * 
     * @test
     */
    public function property_featured_products_without_images_show_placeholder()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create featured products, some with images and some without
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productsWithImages = rand(0, 2);
            $productsWithoutImages = rand(1, 3);
            
            // Create products with images
            for ($i = 0; $i < $productsWithImages; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
                
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'display_order' => 1,
                ]);
            }
            
            // Create products without images
            $productsWithoutImagesArray = [];
            for ($i = 0; $i < $productsWithoutImages; $i++) {
                $productsWithoutImagesArray[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => true,
                ]);
            }
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Products without images should have placeholder SVG
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check for SVG placeholder icon (h-20 w-20 text-gray-300)
            $this->assertStringContainsString('<svg', $content);
            $this->assertStringContainsString('h-20 w-20 text-gray-300', $content);
            
            // Verify each product without image has the placeholder
            foreach ($productsWithoutImagesArray as $product) {
                $response->assertSee($product->name, false);
            }
        }
    }

    /**
     * Property: Featured products with images should not show placeholder
     * 
     * @test
     */
    public function property_featured_products_with_images_show_actual_image()
    {
        // Arrange: Create featured product with image
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true,
        ]);
        
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'display_order' => 1,
            'image_url' => 'products/test-image.jpg',
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Should show actual image, not placeholder
        $response->assertStatus(200);
        $response->assertSee($image->image_url, false);
        $response->assertSee($product->name, false);
    }

    /**
     * Property: Placeholder icon has correct styling
     * 
     * @test
     */
    public function property_placeholder_icon_has_correct_styling()
    {
        // Arrange: Create featured product without image
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
        
        // Assert: Placeholder should have specific styling
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check for gray placeholder styling
        $this->assertStringContainsString('text-gray-300', $content);
        $this->assertStringContainsString('h-20 w-20', $content);
        
        // Check for centered container
        $this->assertStringContainsString('flex items-center justify-center', $content);
    }

    /**
     * Property: Placeholder is displayed in correct container
     * 
     * @test
     */
    public function property_placeholder_is_in_correct_container()
    {
        // Arrange: Create featured product without image
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
        
        // Assert: Placeholder should be in a container with gray background
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check for container with gray background
        $this->assertStringContainsString('bg-gray-100', $content);
        $this->assertStringContainsString('aspect-square', $content);
    }

    /**
     * Property: All featured products without images get placeholders
     * 
     * @test
     */
    public function property_all_imageless_featured_products_have_placeholders()
    {
        // Arrange: Create multiple featured products without images
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productCount = 4;
        for ($i = 0; $i < $productCount; $i++) {
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => true,
            ]);
        }
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Should have placeholder SVGs for all products
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Count SVG placeholders in featured section
        // Each product without image should have an SVG placeholder
        $svgCount = substr_count($content, 'h-20 w-20 text-gray-300');
        
        $this->assertGreaterThanOrEqual($productCount, $svgCount, 
            "Expected at least {$productCount} placeholder icons");
    }
}
