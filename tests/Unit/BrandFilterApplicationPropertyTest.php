<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 5: Brand filter application
 * Validates: Requirements 3.3
 */
class BrandFilterApplicationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 5: Brand filter application
     * For any brand ID selected in the filter, all displayed products should have that brand_id
     * 
     * @test
     */
    public function property_brand_filter_returns_only_products_from_selected_brand()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple brands with products
            $brands = Brand::factory()->count(rand(3, 5))->create([
                'is_active' => true,
            ]);
            
            $category = Category::factory()->create(['is_active' => true]);
            
            // Create products for each brand
            foreach ($brands as $brand) {
                Product::factory()->count(rand(2, 5))->create([
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'status' => 'active',
                ]);
            }
            
            // Pick a random brand to filter by
            $selectedBrand = $brands->random();
            
            // Act: Request dashboard with brand filter
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['brand' => $selectedBrand->id]));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Get all products that should be displayed (from selected brand)
            $expectedProducts = Product::where('brand_id', $selectedBrand->id)
                ->where('status', 'active')
                ->get();
            
            // All expected products should be visible in the main product grid
            foreach ($expectedProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Products from other brands should NOT be visible in the main product grid
            // Note: Featured products section may show products from other brands
            $otherProducts = Product::where('brand_id', '!=', $selectedBrand->id)
                ->where('status', 'active')
                ->where('is_featured', false) // Exclude featured products from this check
                ->get();
            
            foreach ($otherProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }

    /**
     * Property: Brand filter with no matching products shows empty state
     * 
     * @test
     */
    public function property_brand_filter_with_no_products_shows_empty_state()
    {
        // Arrange: Create brand with no products
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create products with other brands
        $otherBrand = Brand::factory()->create(['is_active' => true]);
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'brand_id' => $otherBrand->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        
        // Act: Filter by empty brand
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['brand' => $brand->id]));
        
        // Assert: Should show empty state
        $response->assertStatus(200);
        $response->assertSee('No products found', false);
    }
}
