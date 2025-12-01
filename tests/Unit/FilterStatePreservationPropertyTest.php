<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 9: Filter state preservation
 * Validates: Requirements 3.5, 4.5
 */
class FilterStatePreservationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 9: Filter state preservation
     * For any applied filters and sort options, pagination links should 
     * preserve all query parameters
     * 
     * @test
     */
    public function property_pagination_preserves_all_filters_and_sort()
    {
        // Run the test multiple times with different random data (100 iterations)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create enough products to trigger pagination (more than 12)
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create 15-20 products to ensure pagination
            $productCount = rand(15, 20);
            for ($i = 0; $i < $productCount; $i++) {
                Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(100, 500),
                    'status' => 'active',
                ]);
            }
            
            // Randomly apply filters and sort
            $applySearch = (bool) rand(0, 1);
            $applyCategory = (bool) rand(0, 1);
            $applyBrand = (bool) rand(0, 1);
            $applyPriceRange = (bool) rand(0, 1);
            $applySort = (bool) rand(0, 1);
            
            $filters = [];
            if ($applySearch) {
                $filters['search'] = 'Product';
            }
            if ($applyCategory) {
                $filters['category'] = $category->id;
            }
            if ($applyBrand) {
                $filters['brand'] = $brand->id;
            }
            if ($applyPriceRange) {
                $filters['min_price'] = 100;
                $filters['max_price'] = 500;
            }
            if ($applySort) {
                $sortOptions = ['newest', 'price_low', 'price_high', 'name'];
                $filters['sort'] = $sortOptions[array_rand($sortOptions)];
            }
            
            // Skip if no filters applied
            if (empty($filters)) {
                continue;
            }
            
            // Act: Get first page with filters
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', $filters));
            
            // Assert: Check that pagination links contain all filter parameters
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check if pagination exists (should have "Next" or page numbers)
            if (strpos($content, 'pagination') !== false || strpos($content, 'page=2') !== false) {
                // Verify each filter parameter is in pagination links
                if ($applySearch) {
                    $this->assertStringContainsString('search=Product', $content,
                        "Pagination should preserve search parameter");
                }
                if ($applyCategory) {
                    $this->assertStringContainsString('category=' . $category->id, $content,
                        "Pagination should preserve category parameter");
                }
                if ($applyBrand) {
                    $this->assertStringContainsString('brand=' . $brand->id, $content,
                        "Pagination should preserve brand parameter");
                }
                if ($applyPriceRange) {
                    $this->assertStringContainsString('min_price=100', $content,
                        "Pagination should preserve min_price parameter");
                    $this->assertStringContainsString('max_price=500', $content,
                        "Pagination should preserve max_price parameter");
                }
                if ($applySort) {
                    $this->assertStringContainsString('sort=' . $filters['sort'], $content,
                        "Pagination should preserve sort parameter");
                }
            }
        }
    }

    /**
     * Property: Navigating to page 2 preserves filters
     * 
     * @test
     */
    public function property_page_2_navigation_preserves_filters()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create enough products for pagination
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create 15 products
            for ($i = 0; $i < 15; $i++) {
                Product::factory()->create([
                    'name' => 'Lipstick Product ' . $i,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(200, 400),
                    'status' => 'active',
                ]);
            }
            
            // Apply filters
            $filters = [
                'search' => 'Lipstick',
                'category' => $category->id,
                'min_price' => 200,
                'max_price' => 400,
                'sort' => 'price_low',
                'page' => 2,
            ];
            
            // Act: Navigate to page 2 with filters
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', $filters));
            
            // Assert: Page 2 should still have filters applied
            $response->assertStatus(200);
            
            // Check that we're on page 2 and filters are still active
            $content = $response->getContent();
            
            // Verify filter parameters are in the page
            $this->assertStringContainsString('search=Lipstick', $content);
            $this->assertStringContainsString('category=' . $category->id, $content);
            $this->assertStringContainsString('min_price=200', $content);
            $this->assertStringContainsString('max_price=400', $content);
            $this->assertStringContainsString('sort=price_low', $content);
        }
    }

    /**
     * Property: Sort option is preserved during pagination
     * 
     * @test
     */
    public function property_sort_preserved_during_pagination()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products for pagination
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            for ($i = 0; $i < 15; $i++) {
                Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(100, 500),
                    'status' => 'active',
                ]);
            }
            
            // Random sort option
            $sortOptions = ['newest', 'price_low', 'price_high', 'name'];
            $sortOption = $sortOptions[array_rand($sortOptions)];
            
            // Act: Get page with sort
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'sort' => $sortOption
            ]));
            
            // Assert: Pagination links should contain sort parameter
            $response->assertStatus(200);
            $content = $response->getContent();
            
            if (strpos($content, 'pagination') !== false || strpos($content, 'page=2') !== false) {
                $this->assertStringContainsString('sort=' . $sortOption, $content,
                    "Pagination should preserve sort parameter: {$sortOption}");
            }
        }
    }

    /**
     * Property: Multiple filters are preserved together during pagination
     * 
     * @test
     */
    public function property_multiple_filters_preserved_together()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 30; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products
            $category1 = Category::factory()->create(['is_active' => true]);
            $category2 = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create products in category1
            for ($i = 0; $i < 15; $i++) {
                Product::factory()->create([
                    'name' => 'Serum Product ' . $i,
                    'category_id' => $category1->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(300, 600),
                    'status' => 'active',
                ]);
            }
            
            // Create products in category2 (should be filtered out)
            for ($i = 0; $i < 5; $i++) {
                Product::factory()->create([
                    'name' => 'Other Product ' . $i,
                    'category_id' => $category2->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(100, 200),
                    'status' => 'active',
                ]);
            }
            
            // Apply multiple filters
            $filters = [
                'search' => 'Serum',
                'category' => $category1->id,
                'brand' => $brand->id,
                'min_price' => 300,
                'max_price' => 600,
                'sort' => 'price_high',
            ];
            
            // Act: Get page with all filters
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', $filters));
            
            // Assert: All filters should be in pagination links
            $response->assertStatus(200);
            $content = $response->getContent();
            
            if (strpos($content, 'pagination') !== false || strpos($content, 'page=2') !== false) {
                $this->assertStringContainsString('search=Serum', $content);
                $this->assertStringContainsString('category=' . $category1->id, $content);
                $this->assertStringContainsString('brand=' . $brand->id, $content);
                $this->assertStringContainsString('min_price=300', $content);
                $this->assertStringContainsString('max_price=600', $content);
                $this->assertStringContainsString('sort=price_high', $content);
            }
            
            // Verify that category2 products are not shown
            $response->assertDontSee('Other Product', false);
        }
    }

    /**
     * Property: Filter form maintains selected values after submission
     * 
     * @test
     */
    public function property_filter_form_maintains_selected_values()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 30; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create data
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count(5)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Apply filters
            $filters = [
                'category' => $category->id,
                'brand' => $brand->id,
                'min_price' => 100,
                'max_price' => 500,
            ];
            
            // Act: Submit filters
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', $filters));
            
            // Assert: Form should have selected values
            $response->assertStatus(200);
            $content = $response->getContent();
            
            // Check that filter values are present in the form
            $this->assertStringContainsString('value="' . $category->id . '"', $content);
            $this->assertStringContainsString('value="' . $brand->id . '"', $content);
            $this->assertStringContainsString('value="100"', $content);
            $this->assertStringContainsString('value="500"', $content);
        }
    }
}
