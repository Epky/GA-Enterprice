<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Feature: customer-dashboard-redesign, Property 10: Description truncation
 * Validates: Requirements 4.5
 */
class ProductDescriptionTruncationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 10: Description truncation
     * For any product description longer than 60 characters, the displayed text 
     * should be truncated to 60 characters or less
     * 
     * @test
     */
    public function property_long_descriptions_truncated_to_60_characters_or_less()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with long description (> 60 characters)
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Generate a description longer than 60 characters
            $longDescription = str_repeat('This is a test description. ', rand(3, 10));
            $this->assertGreaterThan(60, strlen($longDescription), 
                "Test setup failed: description should be longer than 60 characters");
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'description' => $longDescription,
            ]);
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: The displayed description should be truncated
            $response->assertStatus(200);
            
            // The truncated version should be present
            $truncated = Str::limit($longDescription, 60);
            $response->assertSee($truncated, false);
            
            // The full long description should NOT be present in the product card
            // (it might be in other places like product detail page)
            $content = $response->getContent();
            
            // Extract the product card section
            $productCardPattern = '/<div class="p-4">.*?' . preg_quote($product->name, '/') . '.*?<\/div>/s';
            if (preg_match($productCardPattern, $content, $matches)) {
                $productCardHtml = $matches[0];
                
                // The full description should not appear in the product card
                $this->assertStringNotContainsString($longDescription, $productCardHtml,
                    "Full description should not appear in product card");
                    
                // The truncated description should appear
                $this->assertStringContainsString(substr($truncated, 0, 50), $productCardHtml,
                    "Truncated description should appear in product card");
            }
        }
    }

    /**
     * Property: Short descriptions (60 chars or less) should not be truncated
     * 
     * @test
     */
    public function property_short_descriptions_not_truncated()
    {
        // Test with descriptions of various lengths up to 60 characters
        for ($length = 10; $length <= 60; $length += 10) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with short description
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $shortDescription = substr(str_repeat('Short desc ', 10), 0, $length);
            $this->assertLessThanOrEqual(60, strlen($shortDescription),
                "Test setup failed: description should be 60 characters or less");
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'description' => $shortDescription,
            ]);
            
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: The full description should be present (no truncation)
            $response->assertStatus(200);
            $response->assertSee($shortDescription, false);
        }
    }

    /**
     * Property: Truncation should be consistent across all products
     * 
     * @test
     */
    public function property_truncation_consistent_across_all_products()
    {
        // Arrange: Create multiple products with long descriptions
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productCount = rand(3, 6);
        $products = [];
        
        for ($i = 0; $i < $productCount; $i++) {
            $longDescription = str_repeat("Product {$i} description text. ", rand(5, 10));
            
            $products[] = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'description' => $longDescription,
            ]);
        }
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: All products should have truncated descriptions
        $response->assertStatus(200);
        
        foreach ($products as $product) {
            $truncated = Str::limit($product->description, 60);
            $response->assertSee($truncated, false);
        }
    }

    /**
     * Property: Empty or null descriptions should be handled gracefully
     * 
     * @test
     */
    public function property_empty_descriptions_handled_gracefully()
    {
        // Test with null and empty descriptions
        $descriptions = [null, '', '   '];
        
        foreach ($descriptions as $description) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'description' => $description,
            ]);
            
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Page should render without errors
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: Truncation should preserve word boundaries when possible
     * 
     * @test
     */
    public function property_truncation_uses_str_limit_helper()
    {
        // Arrange: Create product with description that will be truncated
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $description = "This is a very long product description that definitely exceeds sixty characters and should be truncated properly.";
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'description' => $description,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: The truncated version using Str::limit should be present
        $response->assertStatus(200);
        
        $expectedTruncated = Str::limit($description, 60);
        $response->assertSee($expectedTruncated, false);
        
        // The ellipsis (...) should be present for truncated descriptions
        $this->assertStringContainsString('...', $expectedTruncated,
            "Truncated description should contain ellipsis");
    }
}
