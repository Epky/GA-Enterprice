<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 7: Filter persistence during pagination
 * Validates: Requirements 3.6
 */
class FilterPersistenceDuringPaginationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 7: Filter persistence during pagination
     * For any set of active filters, when navigating to a different page, 
     * all filter parameters should remain in the URL query string
     * 
     * @test
     */
    public function property_filters_persist_in_pagination_links()
    {
        // Run the test multiple times with different random filter combinations
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create enough products to trigger pagination (more than 12)
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            Product::factory()->count(30)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Generate random filter parameters (ensure at least one)
            $filters = [];
            $filterOptions = [
                'category' => $category->id,
                'brand' => $brand->id,
                'min_price' => rand(10, 100),
                'max_price' => rand(200, 500),
            ];
            
            // Randomly select 1-4 filters
            $numFilters = rand(1, 4);
            $selectedKeys = array_rand($filterOptions, $numFilters);
            if (!is_array($selectedKeys)) {
                $selectedKeys = [$selectedKeys];
            }
            
            foreach ($selectedKeys as $key) {
                $filters[$key] = $filterOptions[$key];
            }
            
            // Act: Request dashboard with filters (page 1 will have pagination links)
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', $filters));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Check that pagination links contain all filter parameters
            foreach ($filters as $key => $value) {
                $response->assertSee($key . '=' . $value, false);
            }
        }
    }

    /**
     * Property: Specific test for category filter persistence
     * 
     * @test
     */
    public function property_category_filter_persists_in_pagination()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(25)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act - Request page 2 with category filter
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['category' => $category->id, 'page' => 2]));
        
        // Assert - The category filter should still be in the pagination links
        $response->assertStatus(200);
        $response->assertSee('category=' . $category->id, false);
    }

    /**
     * Property: Specific test for brand filter persistence
     * 
     * @test
     */
    public function property_brand_filter_persists_in_pagination()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(25)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act - Request page 2 with brand filter
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['brand' => $brand->id, 'page' => 2]));
        
        // Assert - The brand filter should still be in the pagination links
        $response->assertStatus(200);
        $response->assertSee('brand=' . $brand->id, false);
    }

    /**
     * Property: Multiple filters persist together
     * 
     * @test
     */
    public function property_multiple_filters_persist_together_in_pagination()
    {
        // Arrange - Create enough products to ensure pagination
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(30)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        $filters = [
            'category' => $category->id,
            'brand' => $brand->id,
            'min_price' => 50,
            'max_price' => 300,
        ];
        
        // Act - Request first page with filters
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', $filters));
        
        // Assert - All filters should be in pagination links
        $response->assertStatus(200);
        foreach ($filters as $key => $value) {
            $response->assertSee($key . '=' . $value, false);
        }
    }
}
