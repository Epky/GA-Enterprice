<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 18: Pagination range calculation
 * Validates: Requirements 6.4
 */
class PaginationRangeCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 18: Pagination range calculation
     * For any page number and page size, the displayed range (X to Y) should 
     * correctly calculate based on (page-1) * pageSize + 1 to min(page * pageSize, total)
     * 
     * @test
     */
    public function property_pagination_range_calculated_correctly_for_all_pages()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create random number of products (between 25 and 60)
            $totalProducts = rand(25, 60);
            
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count($totalProducts)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $pageSize = 12; // As defined in the controller
            $totalPages = ceil($totalProducts / $pageSize);
            
            // Test all pages
            for ($page = 1; $page <= $totalPages; $page++) {
                // Act: Request specific page
                $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                    ->get(route('customer.dashboard', ['page' => $page]));
                
                // Assert: Verify the range calculation
                $response->assertStatus(200);
                
                // Calculate expected range using the formula from the property
                $expectedFirstItem = (($page - 1) * $pageSize) + 1;
                $expectedLastItem = min($page * $pageSize, $totalProducts);
                
                $content = $response->getContent();
                
                // Verify the calculation matches the formula
                $this->assertStringContainsString(
                    "Showing <span class=\"font-semibold\">{$expectedFirstItem}</span>",
                    $content,
                    "Page {$page}: Expected first item = (({$page} - 1) * {$pageSize}) + 1 = {$expectedFirstItem}"
                );
                
                $this->assertStringContainsString(
                    "to <span class=\"font-semibold\">{$expectedLastItem}</span>",
                    $content,
                    "Page {$page}: Expected last item = min({$page} * {$pageSize}, {$totalProducts}) = {$expectedLastItem}"
                );
                
                // Additional verification: ensure the range is valid
                $this->assertGreaterThanOrEqual(1, $expectedFirstItem, "First item should be at least 1");
                $this->assertLessThanOrEqual($totalProducts, $expectedLastItem, "Last item should not exceed total");
                $this->assertGreaterThanOrEqual($expectedFirstItem, $expectedLastItem, "Last item should be >= first item");
            }
        }
    }

    /**
     * Property: First page range should always start at 1
     * 
     * @test
     */
    public function property_first_page_range_starts_at_one()
    {
        // Test with various product counts
        $productCounts = [1, 5, 12, 13, 25, 50];
        
        foreach ($productCounts as $count) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count($count)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Act: Request first page
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['page' => 1]));
            
            // Assert: First item should always be 1
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringContainsString(
                "Showing <span class=\"font-semibold\">1</span>",
                $content,
                "First page should always start at 1 (tested with {$count} products)"
            );
        }
    }

    /**
     * Property: Last page range should end at total count
     * 
     * @test
     */
    public function property_last_page_range_ends_at_total()
    {
        // Test with various product counts that result in partial last pages
        $productCounts = [13, 25, 37, 49]; // These will have partial last pages
        
        foreach ($productCounts as $count) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count($count)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            $pageSize = 12;
            $lastPage = ceil($count / $pageSize);
            
            // Act: Request last page
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['page' => $lastPage]));
            
            // Assert: Last item should be the total count
            $response->assertStatus(200);
            
            $content = $response->getContent();
            
            $this->assertStringContainsString(
                "to <span class=\"font-semibold\">{$count}</span>",
                $content,
                "Last page should end at total count {$count} (page {$lastPage})"
            );
            
            $this->assertStringContainsString(
                "of <span class=\"font-semibold\">{$count}</span> products",
                $content,
                "Total should be {$count}"
            );
        }
    }

    /**
     * Property: Middle pages should have full page size ranges
     * 
     * @test
     */
    public function property_middle_pages_have_full_page_size_ranges()
    {
        // Arrange: Create enough products for at least 3 pages
        $totalProducts = 40; // Will have 4 pages (12, 12, 12, 4)
        
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Product::factory()->count($totalProducts)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        $pageSize = 12;
        
        // Test pages 2 and 3 (middle pages that should be full)
        for ($page = 2; $page <= 3; $page++) {
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['page' => $page]));
            
            // Assert: Should have exactly pageSize items
            $response->assertStatus(200);
            
            $expectedFirstItem = (($page - 1) * $pageSize) + 1;
            $expectedLastItem = $page * $pageSize;
            $rangeSize = $expectedLastItem - $expectedFirstItem + 1;
            
            $this->assertEquals($pageSize, $rangeSize, 
                "Middle page {$page} should have exactly {$pageSize} items");
            
            $content = $response->getContent();
            
            $this->assertStringContainsString(
                "Showing <span class=\"font-semibold\">{$expectedFirstItem}</span>",
                $content
            );
            
            $this->assertStringContainsString(
                "to <span class=\"font-semibold\">{$expectedLastItem}</span>",
                $content
            );
        }
    }

    /**
     * Property: Range calculation should work correctly with filters
     * 
     * @test
     */
    public function property_range_calculation_correct_with_filters()
    {
        // Arrange: Create products with different prices
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 30 products with prices between 100-500
        Product::factory()->count(30)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'base_price' => rand(100, 500),
        ]);
        
        // Act: Apply price filter that will reduce results
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['min_price' => 200, 'max_price' => 400]));
        
        // Assert: Range calculation should still be correct
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Extract the total from the response
        preg_match('/of <span class="font-semibold">(\d+)<\/span> products/', $content, $matches);
        
        if (isset($matches[1])) {
            $filteredTotal = (int)$matches[1];
            
            // If there are filtered results, verify the range
            if ($filteredTotal > 0) {
                $expectedFirstItem = 1;
                $expectedLastItem = min(12, $filteredTotal);
                
                $this->assertStringContainsString(
                    "Showing <span class=\"font-semibold\">{$expectedFirstItem}</span>",
                    $content,
                    "Filtered results should start at 1"
                );
                
                $this->assertStringContainsString(
                    "to <span class=\"font-semibold\">{$expectedLastItem}</span>",
                    $content,
                    "Filtered results should end at min(12, {$filteredTotal})"
                );
            }
        }
    }
}
