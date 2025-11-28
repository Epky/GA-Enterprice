<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 4: Category filter application
 * Validates: Requirements 3.2
 */
class CategoryFilterApplicationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 4: Category filter application
     * For any category ID selected in the filter, all displayed products should have that category_id
     * 
     * @test
     */
    public function property_category_filter_returns_only_products_from_selected_category()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple categories with products
            $categories = Category::factory()->count(rand(3, 5))->create([
                'is_active' => true,
            ]);
            
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create products for each category
            foreach ($categories as $category) {
                Product::factory()->count(rand(2, 5))->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                ]);
            }
            
            // Pick a random category to filter by
            $selectedCategory = $categories->random();
            
            // Act: Request dashboard with category filter
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['category' => $selectedCategory->id]));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Get all products that should be displayed (from selected category)
            $expectedProducts = Product::where('category_id', $selectedCategory->id)
                ->where('status', 'active')
                ->get();
            
            // All expected products should be visible in the main product grid
            foreach ($expectedProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Products from other categories should NOT be visible in the main product grid
            // Note: Featured products section may show products from other categories
            // We need to check that the filtered grid only shows products from selected category
            $otherProducts = Product::where('category_id', '!=', $selectedCategory->id)
                ->where('status', 'active')
                ->where('is_featured', false) // Exclude featured products from this check
                ->get();
            
            foreach ($otherProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }

    /**
     * Property: Category filter with no matching products shows empty state
     * 
     * @test
     */
    public function property_category_filter_with_no_products_shows_empty_state()
    {
        // Arrange: Create category with no products
        $category = Category::factory()->create(['is_active' => true]);
        
        // Create products in other categories
        $otherCategory = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $otherCategory->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act: Filter by empty category
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['category' => $category->id]));
        
        // Assert: Should show empty state
        $response->assertStatus(200);
        $response->assertSee('No products found', false);
    }
}
