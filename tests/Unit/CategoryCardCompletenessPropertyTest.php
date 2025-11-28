<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 19: Category card completeness
 * Validates: Requirements 7.2
 */
class CategoryCardCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 19: Category card completeness
     * For any category displayed in the showcase, the category card should contain 
     * category name, product count, and either an image or default icon
     * 
     * @test
     */
    public function property_category_card_contains_all_required_elements()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create random categories with varying attributes
            $hasImage = (bool) rand(0, 1);
            $productCount = rand(1, 10);
            
            $category = Category::factory()->create([
                'is_active' => true,
                'image_url' => $hasImage ? 'categories/test-image.jpg' : null,
            ]);
            
            // Create active products for this category
            Product::factory()->count($productCount)->create([
                'category_id' => $category->id,
                'status' => 'active',
            ]);
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Check that the category card contains all required elements
            $response->assertStatus(200);
            
            // Category name should be present
            $response->assertSee($category->name, false);
            
            // Product count should be present
            $response->assertSee($productCount, false);
            $response->assertSee($productCount === 1 ? 'product' : 'products', false);
            
            // Either image or default icon should be present
            if ($hasImage) {
                $response->assertSee($category->image_url, false);
            } else {
                // Check for SVG icon (default icon)
                $response->assertSee('<svg', false);
                $response->assertSee('h-24 w-24 text-purple-300', false);
            }
        }
    }

    /**
     * Property: Category card with zero products should not be displayed
     * 
     * @test
     */
    public function property_category_with_zero_products_not_displayed()
    {
        // Arrange: Create category with no products
        $category = Category::factory()->create([
            'is_active' => true,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Category should not be displayed
        $response->assertStatus(200);
        $response->assertDontSee($category->name, false);
    }

    /**
     * Property: Category card with only inactive products should not be displayed
     * 
     * @test
     */
    public function property_category_with_only_inactive_products_not_displayed()
    {
        // Arrange: Create category with only inactive products
        $category = Category::factory()->create([
            'is_active' => true,
        ]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'status' => 'inactive',
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Category should not be displayed
        $response->assertStatus(200);
        $response->assertDontSee($category->name, false);
    }
}
