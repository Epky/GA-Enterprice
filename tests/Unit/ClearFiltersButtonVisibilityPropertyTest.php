<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 6: Clear filters button visibility
 * Validates: Requirements 3.5
 */
class ClearFiltersButtonVisibilityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 6: Clear filters button visibility
     * For any request with at least one filter parameter (category, brand, min_price, max_price, search), 
     * the "Clear Filters" button should be present in the rendered HTML
     * 
     * @test
     */
    public function property_clear_filters_button_visible_when_any_filter_active()
    {
        // Run the test multiple times with different random filter combinations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create test data
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            Product::factory()->count(5)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Generate random filter parameters (at least one must be set)
            $filters = [];
            $filterTypes = ['category', 'brand', 'min_price', 'max_price', 'search'];
            $selectedFilter = $filterTypes[array_rand($filterTypes)];
            
            switch ($selectedFilter) {
                case 'category':
                    $filters['category'] = $category->id;
                    break;
                case 'brand':
                    $filters['brand'] = $brand->id;
                    break;
                case 'min_price':
                    $filters['min_price'] = rand(10, 100);
                    break;
                case 'max_price':
                    $filters['max_price'] = rand(200, 500);
                    break;
                case 'search':
                    $filters['search'] = 'test search';
                    break;
            }
            
            // Randomly add more filters
            if (rand(0, 1)) {
                $additionalFilter = $filterTypes[array_rand($filterTypes)];
                if ($additionalFilter === 'category' && !isset($filters['category'])) {
                    $filters['category'] = $category->id;
                } elseif ($additionalFilter === 'brand' && !isset($filters['brand'])) {
                    $filters['brand'] = $brand->id;
                } elseif ($additionalFilter === 'min_price' && !isset($filters['min_price'])) {
                    $filters['min_price'] = rand(10, 100);
                } elseif ($additionalFilter === 'max_price' && !isset($filters['max_price'])) {
                    $filters['max_price'] = rand(200, 500);
                } elseif ($additionalFilter === 'search' && !isset($filters['search'])) {
                    $filters['search'] = 'another search';
                }
            }
            
            // Act: Request dashboard with filters
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', $filters));
            
            // Assert: Clear Filters button should be present
            $response->assertStatus(200);
            $response->assertSee('Clear Filters', false);
        }
    }

    /**
     * Property: Clear filters button NOT visible when no filters active
     * 
     * @test
     */
    public function property_clear_filters_button_not_visible_when_no_filters()
    {
        // Arrange: Create test data
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act: Request dashboard without any filters
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Clear Filters button should NOT be present
        $response->assertStatus(200);
        // Check that the actual button/link is not present (not just the comment)
        $response->assertDontSee('class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">', false);
        $response->assertDontSee('>Clear Filters</a>', false);
    }

    /**
     * Property: Clear filters button visible with category filter
     * 
     * @test
     */
    public function property_clear_filters_button_visible_with_category_filter()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['category' => $category->id]));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Clear Filters', false);
    }

    /**
     * Property: Clear filters button visible with brand filter
     * 
     * @test
     */
    public function property_clear_filters_button_visible_with_brand_filter()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['brand' => $brand->id]));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Clear Filters', false);
    }

    /**
     * Property: Clear filters button visible with price filters
     * 
     * @test
     */
    public function property_clear_filters_button_visible_with_price_filters()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act: Test with min_price
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['min_price' => 50]));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Clear Filters', false);
        
        // Clean up
        \DB::table('users')->delete();
        
        // Act: Test with max_price
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['max_price' => 200]));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Clear Filters', false);
    }

    /**
     * Property: Clear filters button visible with search
     * 
     * @test
     */
    public function property_clear_filters_button_visible_with_search()
    {
        // Arrange
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['search' => 'test']));
        
        // Assert
        $response->assertStatus(200);
        $response->assertSee('Clear Filters', false);
    }
}
